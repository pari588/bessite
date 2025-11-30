# Commercial/FHP Motor Specifications - CG Global

## Category Information
- **Category**: Commercial / FHP (Fractional Horsepower)
- **URL**: https://www.cgglobal.com/our_business/Commercial/Commercial-FHP-Motors
- **Type**: Fractional Horsepower Single Phase Motors
- **Application**: Commercial, HVAC, Residential applications
- **Standards**: NEMA MG 1

---

## Motors & Specifications

### 1. Capacitor Start Motors (ID: 48)

**Motor Details**
- **Database ID**: 48
- **Product Name**: Capacitor Start Motors
- **Type**: Single Phase FHP
- **Application**: High starting torque applications
- **Standards**: NEMA MG 1

**Specifications Added**

| Description Title | Output Power | Voltage | Frame Size | Standard |
|---|---|---|---|---|
| Capacitor Start Type | 0.37-3.7 kW | 230V Single Ph | NEMA 48-145T | NEMA MG 1 |
| FHP Commercial | Single Phase | 230V/460V | Compact | Commercial Duty |
| Torque Characteristics | High Starting | 230V | | Premium |

**Key Features**
- Single phase construction
- High starting torque for pump applications
- Capacitor start for efficient starting
- Compact frame sizes
- Commercial duty rated

---

### 2. Capacitor Run Motors (ID: 49)

**Motor Details**
- **Database ID**: 49
- **Product Name**: Capacitor Run Motors
- **Type**: Single Phase FHP
- **Application**: Continuous duty, energy efficient
- **Standards**: NEMA MG 1

**Specifications Added**

| Description Title | Output Power | Voltage | Frame Size | Standard |
|---|---|---|---|---|
| Capacitor Run Type | 0.25-2.2 kW | 230V Single Ph | NEMA 48-145T | NEMA MG 1 |
| FHP Commercial | Single Phase | 230V | Compact | Commercial Duty |
| Efficiency Design | Energy Efficient | | | Premium |

**Key Features**
- Single phase construction
- Run capacitor for efficiency
- Lower starting torque but high efficiency
- Compact frame sizes
- Commercial duty rated
- Energy efficient operation

---

### 3. Permanent Split Capacitor Motors (ID: 50)

**Motor Details**
- **Database ID**: 50
- **Product Name**: Permanent Split Capacitor Motors
- **Type**: Single Phase FHP (PSC)
- **Application**: Continuous duty, reliable operation
- **Standards**: NEMA MG 1

**Specifications Added**

| Description Title | Output Power | Voltage | Frame Size | Standard |
|---|---|---|---|---|
| PSC Motor Type | 0.18-2.2 kW | 230V Single Ph | NEMA 48-145T | NEMA MG 1 |
| FHP Commercial | Single Phase | 230V | Compact | Commercial Duty |
| Continuous Duty | Reliable | | | Standard |

**Key Features**
- Single phase permanent capacitor design
- Simple and reliable construction
- Continuous duty operation
- Lower starting torque
- Compact frame sizes
- Standard commercial grade

---

## Technical Specifications Summary

### Output Power Ranges
- **Capacitor Start**: 0.37-3.7 kW (0.5-5 Hp)
- **Capacitor Run**: 0.25-2.2 kW (0.3-3 Hp)
- **PSC Motors**: 0.18-2.2 kW (0.25-3 Hp)

### Voltage Standards
- **Primary**: 230V Single Phase
- **Alternative**: 460V available
- **Frequency**: 50/60 Hz

### Frame Sizes
- **Standard NEMA Frames**: 48T to 145T
- **Mounting**: TEFC (Totally Enclosed Fan Cooled)
- **Construction**: Compact/Space-saving

### Standards & Certifications
- **NEMA MG 1** - Standard
- **Commercial Duty** - Rating
- **Premium/Standard** - Class

---

## Application Guide

### Capacitor Start Motors
**Best For:**
- Pump applications requiring high starting torque
- Air compressors
- Refrigeration equipment
- Heavy load starting

**Performance:**
- High starting torque
- Single phase operation
- Efficient acceleration

### Capacitor Run Motors
**Best For:**
- Continuous duty applications
- Fan motor applications
- Blower operation
- Energy efficiency priority

**Performance:**
- Good running efficiency
- Smooth operation
- Lower starting torque

### Permanent Split Capacitor Motors
**Best For:**
- Continuous operation at partial load
- Pool pump circulation
- Light commercial HVAC
- Reliability critical

**Performance:**
- Simple design
- Reliable operation
- Lower starting torque
- Good for variable load

---

## Database Implementation

### Table: mx_motor_detail
All specifications stored with status = 1 (active)

**Queries for Verification**

```sql
-- Count specifications per motor
SELECT motorID, COUNT(*) as spec_count
FROM mx_motor_detail
WHERE motorID IN (48, 49, 50)
GROUP BY motorID;

-- View all specs for Capacitor Start Motors
SELECT descriptionTitle, descriptionOutput, descriptionVoltage,
       descriptionFrameSize, descriptionStandard
FROM mx_motor_detail
WHERE motorID = 48
ORDER BY descriptionTitle;

-- Check total Commercial/FHP specs
SELECT COUNT(*) as total_specs
FROM mx_motor_detail
WHERE motorID IN (48, 49, 50);
```

---

## Frontend Display

### Location
- Motor detail pages at: `/motor/[motor-seo-uri]/`
- Specifications table with 5 columns

### Display Format
Table shows:
1. **Description Title** - Specification category
2. **Output Power** - Power rating or type
3. **Voltages** - Operating voltage
4. **Frame Size** - Physical frame designation
5. **Standards** - Compliance standards

### Example URLs
- Capacitor Start: `/motor/capacitor-start-motors/`
- Capacitor Run: `/motor/capacitor-run-motors/`
- PSC Motors: `/motor/permanent-split-capacitor-motors/`

---

## Implementation Details

### Date Completed
- 2025-11-09

### Import Script
- File: `/home/bombayengg/public_html/add_commercial_fhp_motor_specs.php`
- Total Records Added: 9
- Errors: 0
- Success Rate: 100%

### Data Format
All specifications follow the standard mx_motor_detail columns:
- **descriptionTitle** (varchar 100)
- **descriptionOutput** (varchar 20)
- **descriptionVoltage** (varchar 20)
- **descriptionFrameSize** (varchar 20)
- **descriptionStandard** (varchar 50)

---

## Troubleshooting

### Issue: Specifications not displaying
**Solution**: Clear cache
```bash
php /home/bombayengg/public_html/clear_cache.php
```

### Issue: Data truncated
**Solution**: Check column lengths in mx_motor_detail
```sql
DESCRIBE mx_motor_detail;
```

### Issue: Wrong table being queried
**File**: `/home/bombayengg/public_html/xsite/mod/motors/x-motors.inc.php`
**Function**: `getMDetail($motorID)`
**Must use**: `mx_motor_detail` table (NOT mx_motor_specification)

---

## Related Documentation

See also:
- [CG Global Motor Data Extraction Guide](CG_GLOBAL_MOTOR_DATA_EXTRACTION_GUIDE.md)
- [CG Motor Implementation Final](CG_MOTOR_IMPLEMENTATION_FINAL.md)
- [Complete Report](CG_MOTOR_SPECS_COMPLETE_REPORT.md)

---

## Version History
- **v1.0** - Initial Commercial/FHP specifications (2025-11-09)

---

**Status**: ✅ Production Ready
**Last Updated**: 2025-11-09
