<?php

// require_once 'vendor/autoload.php'; // Included by cams-callback.php
use Carbon\Carbon;
use PDO;
use SplObjectStorage;
use Exception;
use Throwable;

// Constants
class ShiftAttendanceConstants
{
    public const DEFAULT_OVERLOOK_TIME = 30;
    public const DEFAULT_THRESHOLD_TIME = 0;
    public const SHIFT_WINDOW_DAYS = [-1, 0, 1]; // -1 (prev day), 0 (current day), 1 (next day)
    public const TIMEZONE = 'Asia/Kolkata'; // Equivalent to +05:30
    public const DATE_FORMAT = 'Y-m-d';
    public const DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const TIME_FORMAT = 'H:i';
    public const STATUSES = [
        'ABSENT' => 'A',
        'PRESENT' => 'P'
    ];
}

// Custom Exception Classes
class AttendanceError extends Exception
{
    protected $code = 'ATTENDANCE_ERROR';

    public function __construct($message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if ($code !== 0) {
            $this->code = $code;
        }
    }
}

class ValidationError extends AttendanceError
{
    public function __construct($message, Throwable $previous = null)
    {
        parent::__construct($message, 'VALIDATION_ERROR', $previous);
    }
}

class ShiftAttendance
{
    /**
     * Helper: Convert HH:mm to minutes
     * @param  string $timeStr
     * @return  int
     */
    private static function timeToMinutes(string $timeStr): int
    {
        list($h, $m) = array_map('intval', explode(':', $timeStr)); 
        return $h * 60 + $m;
    }

    /**
     * Helper: Calculate minutes of day from Carbon object
     * @param  Carbon $m
     * @return  int
     */
    private static function calculateDayMinutes(Carbon $m): int
    {
        return $m->hour * 60 + $m->minute;
    }

    /**
     * Helper: Compute worked minutes with break handling
     * @param  Carbon|null $firstIn
     * @param  Carbon|null $lastOut
     * @param  Carbon|null $breakOut
     * @param  Carbon|null $breakIn
     * @param  array $shift
     * @return  int
     */
    private static function computeWorkedMinutes(?Carbon $firstIn, ?Carbon $lastOut, ?Carbon $breakOut, ?Carbon $breakIn, array $shift): int
    {
        if (!$firstIn || !$lastOut || $lastOut->isBefore($firstIn)) {
            return 0;
        }

        $worked = $lastOut->diffInMinutes($firstIn);
        $breakMinutes = 0;

        if ($breakOut && $breakIn && $breakIn->isAfter($breakOut)) {
            $breakMinutes = $breakIn->diffInMinutes($breakOut);
        } elseif (isset($shift['break_start']) && isset($shift['break_end'])) {
            // If no actual break punches, use configured break duration
            $breakStartMins = self::timeToMinutes($shift['break_start']);
            $breakEndMins = self::timeToMinutes($shift['break_end']);
            if ($breakEndMins > $breakStartMins) {
                $breakMinutes = $breakEndMins - $breakStartMins;
            }
        }

        return max(0, $worked - $breakMinutes);
    }

    /**
     * Finds the working day index (1 for Monday, 7 for Sunday)
     * @param  string $punchDate
     * @return  int
     */
    private static function findWorkingDayIndex(string $punchDate): int
    {
        $today = Carbon::parse($punchDate)->setTimezone(ShiftAttendanceConstants::TIMEZONE);
        return $today->isoWeekday(); // Carbon's isoWeekday is 1 for Mon, 7 for Sun.
    }

    /**
     * Helper: Select firstIn / lastOut based on direction
     * @param  array $punchRows
     * @param  int $direction
     * @return  array {firstIn: Carbon|null, lastOut: Carbon|null, breakOut: Carbon|null, breakIn: Carbon|null}
     */
    private static function selectPunches(array $punchRows, int $direction): array
    {
        $firstIn = null;
        $lastOut = null;
        $breakOut = null;
        $breakIn = null;

        if ($direction === 0) { // punch_type = actual_punch_type
            foreach ($punchRows as $p) {
                $punchMoment = Carbon::parse($p['punch_time'])->setTimezone(ShiftAttendanceConstants::TIMEZONE);
                if ($p['punch_type'] === 0 && $firstIn === null) {
                    $firstIn = $punchMoment;
                }
                if ($p['punch_type'] === 1) {
                    $lastOut = $punchMoment;
                }
                if ($p['punch_type'] === 3) {
                    $breakOut = $punchMoment;
                }
                if ($p['punch_type'] === 4) {
                    $breakIn = $punchMoment;
                }
            }
        } elseif ($direction === 1) { // Zigzag IN/OUT
            $expectIn = true;
            foreach ($punchRows as $p) {
                $punchMoment = Carbon::parse($p['punch_time'])->setTimezone(ShiftAttendanceConstants::TIMEZONE);
                if ($expectIn && $p['punch_type'] === 0) {
                    if ($firstIn === null) {
                        $firstIn = $punchMoment;
                    }
                    $expectIn = false; // next expect OUT
                } elseif (!$expectIn && $p['punch_type'] === 1) {
                    $lastOut = $punchMoment;
                    $expectIn = true; // next expect IN
                }
            }
            if (!$firstIn || !$lastOut) {
                error_log("⚠ Direction 1: Could not form valid IN-OUT zigzag pair from punches");
            }
        } elseif ($direction === 2) { // First IN, Last OUT, Rest Intermediate
            if (count($punchRows) >= 2) {
                $firstIn = Carbon::parse($punchRows[0]['punch_time'])->setTimezone(ShiftAttendanceConstants::TIMEZONE);
                $lastOut = Carbon::parse($punchRows[count($punchRows) - 1]['punch_time'])->setTimezone(ShiftAttendanceConstants::TIMEZONE);
            }
        }
        return ['firstIn' => $firstIn, 'lastOut' => $lastOut, 'breakOut' => $breakOut, 'breakIn' => $breakIn];
    }
    
    /**
     * Internal: Evaluates attendance status and overtime.
     * @param  array $schedule - The overall attendance schedule/policy.
     * @param  array $shift - The specific shift configuration for the day.
     * @param  Carbon $firstIn - First punch in moment.
     * @param  Carbon $lastOut - Last punch out moment.
     * @param  int $workedMinutes - Total worked minutes after breaks.
     * @return  array {status: string, confirmStatus: string, overtime: int}
     */
    private static function evaluateStatusAndOvertime(array $schedule, array $shift, Carbon $firstIn, Carbon $lastOut, int $workedMinutes): array
    {
        $shiftStartMins = self::timeToMinutes($shift['start_time']);
        $shiftEndMins = self::timeToMinutes($shift['end_time']);
        $tStart = $schedule['threshold_start_time'] ?? 0;
        $tEnd = $schedule['threshold_end_time'] ?? 0;
        $minHrs = $schedule['minimum_working_hours'] ?? 0;
        $slab = $schedule['overtime_slab_minute'] ?? 0;

        $inMin = self::calculateDayMinutes($firstIn);
        $outMin = self::calculateDayMinutes($lastOut);
        $workedHours = $workedMinutes / 60;

        $status = ShiftAttendanceConstants::STATUSES['ABSENT'];
        $confirmStatus = ShiftAttendanceConstants::STATUSES['FINALIZED']; // Default to Finalized in this function

        $overtime = 0;

        $policy = $schedule['policy'] ?? 'once_a_day'; // Default to once_a_day if not specified

        if ($policy === 'once_a_day') {
            $status = ShiftAttendanceConstants::STATUSES['PRESENT'];
        } elseif ($policy === 'strict') {
            if ($inMin <= $shiftStartMins + $tStart && $outMin >= $shiftEndMins - $tEnd && $workedHours >= $minHrs) {
                $status = ShiftAttendanceConstants::STATUSES['PRESENT'];
            }
        }

        if ($workedMinutes > ($minHrs * 60 + $slab)) {
            $overtime = floor(($workedMinutes - $minHrs * 60) / 60);
        }

        return ['status' => $status, 'confirmStatus' => $confirmStatus, 'overtime' => $overtime];
    }

    /**
     * Upserts (inserts or updates) an attendance record.
     * @param  PDO $connection - PDO database connection.
     * @param  string $userId - User ID.
     * @param  string $date - Date string (Y-m-d).
     * @param  string|null $inStr - Punch in time string (Y-m-d H:i:s).
     * @param  string|null $outStr - Punch out time string (Y-m-d H:i:s).
     * @param  string $status - Attendance status (P/A).
     * @param  string $confirmStatus - Confirmation status (F/I).
     * @param  array $shift - Shift configuration.
     * @param  int $dayId - Day of week ID.
     * @param  int $workedMins - Total worked minutes.
     * @param  int $overtime - Overtime hours.
     */
    private static function upsertAttendance(PDO $connection, string $userId, string $date, ?string $inStr, ?string $outStr, string $status, string $confirmStatus, array $shift, int $dayId, int $workedMins, int $overtime): void
    {
        $stmt = $connection->prepare(
            'SELECT id FROM camsAttendance WHERE user_id = ? AND DATE(punch_in_time) = ? LIMIT 1'
        );
        $stmt->execute([$userId, $date]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing === false) {
            $stmt = $connection->prepare(
                'INSERT INTO camsAttendance 
                 (user_id, punch_in_time, punch_out_time, status, confirm_status, shift_id, day_id, working_hours, overtime_slab_minute, time_updated)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([$userId, $inStr, $outStr, $status, $confirmStatus, $shift['shift_id'] ?? 9, $dayId, $workedMins / 60, $overtime]);
        } else {
            $stmt = $connection->prepare(
                'UPDATE camsAttendance SET
                 punch_in_time = ?, punch_out_time = ?, status = ?, confirm_status = ?,
                 working_hours = ?, overtime_slab_minute = ?, time_updated = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([$inStr, $outStr, $status, $confirmStatus, $workedMins / 60, $overtime, $existing['id']]);
        }
    }


    /**
     * Validates inputs for handleShiftPunch.
     * @param  string $userId
     * @param  string $punchTime
     * @param  array $shiftConfig
     * @throws  ValidationError
     */
    public static function validateInputs(string $userId, string $punchTime, array $shiftConfig): void
    {
        if (empty($userId)) {
            throw new ValidationError("User ID is required");
        }
        if (empty($punchTime)) {
            throw new ValidationError("Punch time is required");
        }
        if (empty($shiftConfig['shift_hours']) || !is_array($shiftConfig['shift_hours'])) {
            throw new ValidationError("Invalid or missing shift_hours in shift config");
        }
    }

    /**
     * Generates shift objects within a window around the punch moment.
     * @param  Carbon $punchMoment
     * @param  array $shiftConfig
     * @return  array
     */
    private static function generateShiftsInWindow(Carbon $punchMoment, array $shiftConfig): array
    {
        $result = [];
        $shiftHours = $shiftConfig['shift_hours'] ?? [];

        foreach (ShiftAttendanceConstants::SHIFT_WINDOW_DAYS as $offset) {
            $dateStr = $punchMoment->clone()->addDays($offset)->format(ShiftAttendanceConstants::DATE_FORMAT);

            foreach ($shiftHours as $idx => $shift) {
                $start = Carbon::parse("$dateStr {$shift['start_time']}", ShiftAttendanceConstants::TIMEZONE);
                $end = Carbon::parse("$dateStr {$shift['end_time']}", ShiftAttendanceConstants::TIMEZONE);

                // Handle overnight shifts
                if ($end->isBefore($start)) {
                    $end->addDay();
                }

                $overlook = $shiftConfig['Over_look_time'] ?? ShiftAttendanceConstants::DEFAULT_OVERLOOK_TIME;
                $tIn = $shiftConfig['threshold_start_time'] ?? ShiftAttendanceConstants::DEFAULT_THRESHOLD_TIME;
                $tOut = $shiftConfig['threshold_end_time'] ?? ShiftAttendanceConstants::DEFAULT_THRESHOLD_TIME;

                $result[] = [
                    'shift_id' => $idx + 1, // Assign a simple ID
                    'label' => $shift['label'],
                    'start' => $start,
                    'end' => $end,
                    'punchInStart' => $start->clone()->subMinutes($tIn),
                    'punchInEnd' => $start->clone()->addMinutes($tIn),
                    'punchOutStart' => $end->clone()->subMinutes($tOut),
                    'punchOutEnd' => $end->clone()->addMinutes($tOut),
                    'fullStart' => $start->clone()->subMinutes($overlook),
                    'fullEnd' => $end->clone()->addMinutes($overlook)
                ];
            }
        }
        return $result;
    }

    /**
     * Rebuilds attendance records based on punches and shifts.
     * @param  PDO $connection
     * @param  string $userId
     * @param  array $punches Array of Carbon objects.
     * @param  array $shifts Array of shift configurations.
     * @param  string $policy
     */
    private static function rebuildAttendanceFromPunches(PDO $connection, string $userId, array $punches, array $shifts, string $policy): void
    {
        $used = new SplObjectStorage(); // To track used Carbon objects
        $attendanceMap = []; // Map to store existing attendance records

        // Preload attendance
        foreach ($shifts as $shift) {
            $key = sprintf('%s-%d', $shift['start']->format(ShiftAttendanceConstants::DATE_FORMAT), $shift['shift_id']);
            $stmt = $connection->prepare(
                'SELECT id FROM camsAttendance WHERE user_id = ? AND shift_id = ? AND DATE(pdate) = DATE(?) LIMIT 1'
            );
            $stmt->execute([$userId, $shift['shift_id'], $shift['start']->format(ShiftAttendanceConstants::DATE_FORMAT)]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $attendanceMap[$key] = $rows[0];
            }
        }


        if ($policy === 'once_a_day') {
            $groupedByDate = [];
            foreach ($punches as $punch) {
                $dateKey = $punch->format(ShiftAttendanceConstants::DATE_FORMAT);
                if (!isset($groupedByDate[$dateKey])) {
                    $groupedByDate[$dateKey] = [];
                }
                $groupedByDate[$dateKey][] = $punch;
            }

            foreach ($groupedByDate as $pDate => $punchList) {
                usort($punchList, function ($a, $b) {
                    return $a->timestamp - $b->timestamp;
                });
                $firstPunch = $punchList[0];
                $lastPunch = $punchList[count($punchList) - 1];

                $matchedShift = null;
                foreach ($shifts as $shift) {
                    if ($firstPunch->greaterThanOrEqualTo($shift['fullStart']) && $firstPunch->lessThanOrEqualTo($shift['fullEnd'])) {
                        $matchedShift = $shift;
                        break;
                    }
                }

                if (!$matchedShift) {
                    error_log(sprintf('⚠ No shift match on %s for user %s', $pDate, $userId));
                    continue;
                }

                $punchInStr = $firstPunch->format(ShiftAttendanceConstants::DATETIME_FORMAT);
                $punchOutStr = ($lastPunch->isAfter($firstPunch))
                    ? $lastPunch->format(ShiftAttendanceConstants::DATETIME_FORMAT)
                    : null;

                $key = sprintf('%s-%d', $matchedShift['start']->format(ShiftAttendanceConstants::DATE_FORMAT), $matchedShift['shift_id']);
                $existing = $attendanceMap[$key] ?? null;

                $status = ($punchInStr && $punchOutStr) ? ShiftAttendanceConstants::STATUSES['PRESENT'] : ShiftAttendanceConstants::STATUSES['ABSENT'];

                if ($existing) {
                    $stmt = $connection->prepare(
                        'UPDATE camsAttendance 
                         SET punch_in_time = ?, punch_out_time = ?, status = ?, time_updated = NOW()
                         WHERE id = ?'
                    );
                    $stmt->execute([$punchInStr, $punchOutStr, $status, $existing['id']]);
                    error_log(sprintf('🔄 Updated (once_a_day) IN: %s, OUT: %s', $punchInStr, $punchOutStr));
                } else {
                    $stmt = $connection->prepare(
                        'INSERT INTO camsAttendance 
                         (pdate, user_id, punch_in_time, punch_out_time, shift_id, day_id, status, time_updated)
                         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                    );
                    $stmt->execute([
                        $pDate,
                        $userId,
                        $punchInStr,
                        $punchOutStr,
                        $matchedShift['shift_id'],
                        $firstPunch->isoWeekday(),
                        $status
                    ]);
                    error_log(sprintf('➕ Inserted (once_a_day) IN: %s, OUT: %s', $punchInStr, $punchOutStr));
                }

                $used->attach($firstPunch);
                $used->attach($lastPunch);
            }

            error_log(sprintf('✅ Processed once_a_day policy for user %s', $userId));
            return;
        }

        // STRICT POLICY (default)
        foreach ($punches as $punchIn) {
            if ($used->contains($punchIn)) continue;

            foreach ($shifts as $shift) {
                if ($punchIn->greaterThanOrEqualTo($shift['punchInStart']) && $punchIn->lessThanOrEqualTo($shift['punchInEnd'])) {
                    foreach ($punches as $punchOut) {
                        if ($used->contains($punchOut)) continue;
                        if ($punchOut->greaterThanOrEqualTo($shift['punchOutStart']) && $punchOut->lessThanOrEqualTo($shift['punchOutEnd'])) {
                            $key = sprintf('%s-%d', $shift['start']->format(ShiftAttendanceConstants::DATE_FORMAT), $shift['shift_id']);
                            $punchInStr = $punchIn->format(ShiftAttendanceConstants::DATETIME_FORMAT);
                            $punchOutStr = $punchOut->format(ShiftAttendanceConstants::DATETIME_FORMAT);

                            if (isset($attendanceMap[$key])) {
                                $existing = $attendanceMap[$key];
                                $stmt = $connection->prepare(
                                    'UPDATE camsAttendance 
                                     SET punch_in_time = ?, punch_out_time = ?, status = ?, time_updated = NOW()
                                     WHERE id = ?'
                                );
                                $stmt->execute([$punchInStr, $punchOutStr, ShiftAttendanceConstants::STATUSES['PRESENT'], $existing['id']]);
                            } else {
                                $stmt = $connection->prepare(
                                    'INSERT INTO camsAttendance 
                                     (pdate, user_id, punch_in_time, punch_out_time, shift_id, day_id, status, time_updated)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
                                );
                                $stmt->execute([
                                    $shift['start']->format(ShiftAttendanceConstants::DATE_FORMAT),
                                    $userId,
                                    $punchInStr,
                                    $punchOutStr,
                                    $shift['shift_id'],
                                    $punchIn->isoWeekday(),
                                    ShiftAttendanceConstants::STATUSES['PRESENT']
                                ]);
                            }

                            $used->attach($punchIn);
                            $used->attach($punchOut);
                            break 2; // Break from inner (punchOut) loop and outer (shift) loop
                        }
                    }
                }
            }
        }
        error_log(sprintf('✅ Processed strict policy for user %s. Punches used: %d', $userId, $used->count()));
    }

    /**
     * Main entry for shift attendance processing.
     * @param  PDO $connection - PDO database connection.
     * @param  string $userId - User ID.
     * @param  string $punchTimeStr - Punch time string (YYYY-MM-DD HH:mm:ss).
     * @param  array $shiftConfig - Shift configuration.
     * @throws  AttendanceError
     */
    public static function handleShiftPunch(PDO $connection, string $userId, string $punchTimeStr, array $shiftConfig): void
    {
        try {
            self::validateInputs($userId, $punchTimeStr, $shiftConfig);

            $punchMoment = Carbon::parse($punchTimeStr)->setTimezone(ShiftAttendanceConstants::TIMEZONE);
            if (!$punchMoment->isValid()) {
                throw new ValidationError('Invalid punch time');
            }

            $startWindow = $punchMoment->clone()->subHours(24)->format(ShiftAttendanceConstants::DATETIME_FORMAT);
            $endWindow = $punchMoment->clone()->addHours(24)->format(ShiftAttendanceConstants::DATETIME_FORMAT);

            $policy = $shiftConfig['policy'] ?? 'strict';

            error_log(sprintf('🔁 Reprocessing user %s from %s to %s (%s)', $userId, $startWindow, $endWindow, $policy));

            $stmt = $connection->prepare(
                'SELECT punch_time, punch_type FROM camsPunch WHERE user_id = ? AND punch_time BETWEEN ? AND ? ORDER BY punch_time ASC'
            );
            $stmt->execute([$userId, $startWindow, $endWindow]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $punches = array_map(function ($row) {
                return Carbon::parse($row['punch_time'])->setTimezone(ShiftAttendanceConstants::TIMEZONE);
            }, $rows);
            $shifts = self::generateShiftsInWindow($punchMoment, $shiftConfig);

            self::rebuildAttendanceFromPunches($connection, $userId, $punches, $shifts, $policy);

        } catch (Throwable $error) {
            error_log('❌ Error in handleShiftPunch: ' . $error->getMessage());
            throw $error;
        }
    }
}