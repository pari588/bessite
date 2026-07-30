# XAdmin Module Creation Guide

**Last Updated:** December 3, 2025

This guide details how to create a new module in the `xadmin` backend system. The system uses a convention-based architecture where each module resides in its own directory within `xadmin/mod/` and consists of three core files.

---

## 1. PREREQUISITES

Before creating files, you need a database table.

### Database Naming Conventions
*   **Table Name:** `mx_{module_name}` (e.g., `mx_events`, `mx_testimonials`)
*   **Primary Key:** `{module_name}ID` (e.g., `eventID`, `testimonialID`)
*   **Required Columns:**
    *   `status` (int, 1=Active, 0=Trash) - **CRITICAL** for the system to work.
    *   `seoUri` (varchar) - If the module has a frontend detail page.
    *   `xOrder` (int) - If manual sorting is required.

**Example SQL:**
```sql
CREATE TABLE `mx_events` (
  `eventID` int(11) NOT NULL AUTO_INCREMENT,
  `eventTitle` varchar(255) DEFAULT NULL,
  `eventDate` date DEFAULT NULL,
  `eventImage` varchar(255) DEFAULT NULL,
  `eventDesc` text,
  `status` int(1) DEFAULT '1',
  PRIMARY KEY (`eventID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 2. DIRECTORY STRUCTURE

Create a folder in `xadmin/mod/` matching your module name.

**Path:** `xadmin/mod/events/`

**Required Files:**
1.  `x-events.inc.php` (Controller logic)
2.  `x-events-list.php` (Listing view)
3.  `x-events-add-edit.php` (Add/Edit form)

---

## 3. FILE IMPLEMENTATION

### A. Controller: `x-{module}.inc.php`

This file handles form submissions (AJAX).

**Key Components:**
*   **`add{Module}()`**: Handles INSERT.
*   **`update{Module}()`**: Handles UPDATE.
*   **`cleanTitle() / cleanHtml()`**: Sanitization helpers.
*   **`mxGetFileName()`**: Handles file uploads.
*   **`setModVars()`**: Configures table and upload paths.

**Template:**
```php
<?php
function addEvents() {
    global $DB;
    
    // 1. Sanitize Input
    if (isset($_POST["eventTitle"])) $_POST["eventTitle"] = cleanTitle($_POST["eventTitle"]);
    if (isset($_POST["eventDesc"])) $_POST["eventDesc"] = cleanHtml($_POST["eventDesc"]);
    
    // 2. Handle File Uploads
    $_POST["eventImage"] = mxGetFileName("eventImage");
    
    // 3. Insert
    $DB->table = $DB->pre . "events";
    $DB->data = $_POST;
    
    if ($DB->dbInsert()) {
        setResponse(array("err" => 0, "param" => "id=" . $DB->insertID));
    } else {
        setResponse(array("err" => 1));
    }
}

function updateEvents() {
    global $DB;
    $id = intval($_POST["eventID"]);
    
    if (isset($_POST["eventTitle"])) $_POST["eventTitle"] = cleanTitle($_POST["eventTitle"]);
    if (isset($_POST["eventDesc"])) $_POST["eventDesc"] = cleanHtml($_POST["eventDesc"]);
    $_POST["eventImage"] = mxGetFileName("eventImage");

    $DB->table = $DB->pre . "events";
    $DB->data = $_POST;
    
    if ($DB->dbUpdate("eventID=?", "i", array($id))) {
        setResponse(array("err" => 0, "param" => "id=$id"));
    } else {
        setResponse(array("err" => 1));
    }
}

// Router Logic
if (isset($_POST["xAction"])) {
    require_once("../../../core/core.inc.php");
    require_once("../../inc/site.inc.php");
    $MXRES = mxCheckRequest(); // Security Check
    if ($MXRES["err"] == 0) {
        switch ($_POST["xAction"]) {
            case "ADD": addEvents(); break;
            case "UPDATE": updateEvents(); break;
            case "mxDelFile": 
                // Handle file deletion via AJAX
                $param = array("dir" => "events", "tbl" => "events", "pk" => "eventID");
                mxDelFile(array_merge($_REQUEST, $param));
                break;
        }
    }
    echo json_encode($MXRES);
} else {
    // Configuration for List/Form pages
    if (function_exists("setModVars")) 
        setModVars(array("TBL" => "events", "PK" => "eventID", "UDIR" => array("eventImage" => "events")));
}
?>
```

### B. List View: `x-{module}-list.php`

Displays records in a grid with search filters.

**Key Components:**
*   **`$arrSearch`**: Configuration for the search bar.
*   **`$MXCOLS`**: Defines table headers and database fields to show.
*   **`getMAction()`**: Renders checkbox and delete buttons.

**Template:**
```php
<?php
// 1. Configure Search
$arrSearch = array(
    array("type" => "text", "name" => "eventTitle", "title" => "Title", "where" => "AND eventTitle LIKE CONCAT('%',?,'%')", "dtype" => "s")
);

// 2. Build Query
$MXFRM = new mxForm();
$strSearch = $MXFRM->getFormS($arrSearch);
$DB->vals = $MXFRM->vals;
array_unshift($DB->vals, $MXSTATUS); // $MXSTATUS handles Trash/Live view
$DB->types = "i" . $MXFRM->types;
$DB->sql = "SELECT " . $MXMOD["PK"] . " FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=?" . $MXFRM->where;
$DB->dbQuery();
$MXTOTREC = $DB->numRows;

echo $strSearch;
?>

<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <div class="wrap-data">
        <?php if ($MXTOTREC > 0) { 
            // 3. Define Columns: [Title, DBField, Attributes, EditLink?]
            $MXCOLS = array(
                array("Image", "eventImage", 'width="5%" align="left"', "", "nosort"),
                array("Title", "eventTitle", 'width="40%" align="left"', true),
                array("Date", "eventDate", 'width="10%" align="center"')
            );
            
            // 4. Fetch Data
            $DB->vals = $MXFRM->vals;
            array_unshift($DB->vals, $MXSTATUS);
            $DB->types = "i" . $MXFRM->types;
            $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? " . $MXFRM->where . mxOrderBy("eventID DESC") . mxQryLimit();
            $DB->dbRows();
        ?>
        <table class="tbl-list" width="100%">
            <thead><tr><?php echo getListTitle($MXCOLS); ?></tr></thead>
            <tbody>
                <?php foreach ($DB->rows as $d) { 
                    // Process Image
                    if ($d["eventImage"]) $d["eventImage"] = getFile(array("path" => "events/" . $d["eventImage"]));
                ?>
                <tr>
                    <?php echo getMAction("mid", $d["eventID"]); ?>
                    <?php foreach ($MXCOLS as $v) { ?>
                        <td <?php echo $v[2]; ?>>
                            <?php echo (isset($v[3]) && $v[3]) ? getViewEditUrl("id=".$d["eventID"], $d[$v[1]]) : $d[$v[1]]; ?>
                        </td>
                    <?php } ?>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <?php } else { echo '<div class="no-records">No records found</div>'; } ?>
    </div>
</div>
```

### C. Form View: `x-{module}-add-edit.php`

Generates the Add/Edit form.

**Key Components:**
*   **`$arrForm`**: Array defining fields.
*   **`$MXFRM->getForm()`**: Renders the form HTML.
*   **`$MXFRM->closeForm()`**: Renders hidden fields and JS initialization.

**Field Types:**
*   `text`: Standard input.
*   `editor`: CKEditor WYSIWYG.
*   `file`: File uploader (AJAX).
*   `date`: Date picker.
*   `select`: Dropdown (requires `value` property with HTML options).

**Template:**
```php
<?php
$id = 0;
$D = array();
if ($TPL->pageType == "edit" || $TPL->pageType == "view") {
    $id = intval($_GET["id"] ?? 0);
    $DB->vals = array(1, $id);
    $DB->types = "ii";
    $DB->sql = "SELECT * FROM `" . $DB->pre . $MXMOD["TBL"] . "` WHERE status=? AND `" . $MXMOD["PK"] . "` =?";
    $D = $DB->dbRow();
}

// Form Configuration
$arrForm = array(
    array("type" => "text", "name" => "eventTitle", "value" => $D["eventTitle"] ?? "", "title" => "Event Title", "validate" => "required"),
    array("type" => "date", "name" => "eventDate", "value" => $D["eventDate"] ?? "", "title" => "Event Date"),
    array("type" => "editor", "name" => "eventDesc", "value" => $D["eventDesc"] ?? "", "title" => "Description"),
    array("type" => "file", "name" => "eventImage", "value" => array($D["eventImage"] ?? "", $id), "title" => "Banner Image", "params" => array("EXT" => "jpg|jpeg|png|webp"), "udir" => "events")
);

$MXFRM = new mxForm();
?>
<div class="wrap-right">
    <?php echo getPageNav(); ?>
    <form class="wrap-data" name="frmAddEdit" id="frmAddEdit" method="post" enctype="multipart/form-data">
        <div class="wrap-form">
            <ul class="tbl-form">
                <?php echo $MXFRM->getForm($arrForm); ?>
            </ul>
        </div>
        <?php echo $MXFRM->closeForm(); ?>
    </form>
</div>
```

---

## 4. REGISTERING THE MODULE & MENU ACCESS CONTROL

### 4.1 Menu Structure

The admin sidebar menu is stored in `mx_x_admin_menu` table.

**Table Structure:**
| Column | Type | Description |
|--------|------|-------------|
| `adminMenuID` | int | Primary key |
| `menuTitle` | varchar(100) | Display name in sidebar |
| `seoUri` | varchar(100) | **MUST match directory name** (e.g., `events`) |
| `parentID` | int | 0 = top-level, or parent menu's adminMenuID |
| `xOrder` | int | Sort order (lower = higher in menu) |
| `hideMenu` | tinyint | 0 = show, 1 = hide from sidebar |
| `forceNav` | varchar(10) | Force page type: `list`, `add`, `edit`, or empty |
| `params` | varchar(100) | Extra URL parameters |
| `status` | tinyint | 1 = active, 0 = inactive |

**Insert Menu SQL:**
```sql
-- Top-level menu item
INSERT INTO `mx_x_admin_menu`
(`menuTitle`, `seoUri`, `parentID`, `xOrder`, `status`, `hideMenu`)
VALUES
('Events', 'events', 0, 99, 1, 0);

-- Sub-menu item (under a parent)
INSERT INTO `mx_x_admin_menu`
(`menuTitle`, `seoUri`, `parentID`, `xOrder`, `status`, `hideMenu`)
VALUES
('Event Categories', 'event-category', 123, 1, 1, 0);  -- 123 = parent's adminMenuID
```

### 4.2 Role-Based Access Control (RBAC)

The system uses three tables for access control:

```
┌─────────────────┐      ┌─────────────────────┐      ┌─────────────────┐
│ mx_x_admin_user │      │ mx_x_admin_role_    │      │ mx_x_admin_menu │
│                 │      │       access        │      │                 │
│ userID          │      │                     │      │ adminMenuID     │
│ roleID ─────────┼──┐   │ accessID            │   ┌──┼ menuTitle       │
│ userName        │  │   │ roleID ◄────────────┼───┤  │ seoUri          │
│ ...             │  │   │ adminMenuID ◄───────┼───┘  │ parentID        │
└─────────────────┘  │   │ accessType (JSON)   │      │ ...             │
                     │   │ status              │      └─────────────────┘
┌─────────────────┐  │   └─────────────────────┘
│ mx_x_admin_role │  │
│                 │  │
│ roleID ◄────────┼──┘
│ roleName        │
│ rolePage        │ (default landing page for this role)
│ ...             │
└─────────────────┘
```

**Access Types (JSON array in `accessType` column):**
```json
["view"]                    // Read-only access
["view", "add"]             // Can view and add
["view", "add", "edit"]     // Can view, add, and edit
["view", "add", "edit", "delete"]  // Full access
```

### 4.3 Creating a Role

**Step 1: Insert Role**
```sql
INSERT INTO `mx_x_admin_role`
(`roleName`, `roleEmail`, `rolePage`, `status`)
VALUES
('Event Manager', 'events@company.com', 'events', 1);
-- Note: rolePage = default landing page after login (must match seoUri)
```

**Step 2: Assign Menu Access to Role**
```sql
-- Get the roleID from previous insert (e.g., 16)
-- Get the adminMenuID for the module (e.g., 99 for 'events')

INSERT INTO `mx_x_admin_role_access`
(`roleID`, `adminMenuID`, `accessType`, `status`)
VALUES
(16, 99, '["view", "add", "edit", "delete"]', 1);
```

**Step 3: Assign Role to User**
```sql
UPDATE `mx_x_admin_user`
SET roleID = 16
WHERE userID = 25;
```

### 4.4 How Menu Access Works

The menu rendering happens in `xadmin/core-admin/common.inc.php`:

```php
// getAdminSMenu() function - simplified logic
foreach ($menuItems as $menuItem) {
    // Check if user has access to this menu item
    if (isset($TPL->mAccess[$menuItem["seoUri"]])) {
        // User has access - show menu item
        echo '<li><a href="...">' . $menuItem["menuTitle"] . '</a></li>';

        // Show "Add" button only if user has "add" permission
        if (in_array("add", $TPL->mAccess[$menuItem["seoUri"]])) {
            echo '<a href="...-add/" class="add">+</a>';
        }
    }
    // If no access, menu item is NOT displayed
}
```

**Access is loaded in `tpl.class.inc.php`:**
```php
// For SUPER admin (roleID = 'SUPER')
$this->mAccess[$seoUri] = $MXACCESS;  // Full access to everything

// For regular roles
$sql = "SELECT A.adminMenuID, A.accessType, M.seoUri
        FROM mx_x_admin_role_access AS A
        LEFT JOIN mx_x_admin_menu AS M ON M.adminMenuID = A.adminMenuID
        WHERE A.roleID = ?";
// Result: $this->mAccess['events'] = ['view', 'add', 'edit', 'delete']
```

### 4.5 Complete Example: Adding Module with Role Access

**Scenario:** Create "Events" module accessible only to "Event Managers"

```sql
-- 1. Create the menu entry
INSERT INTO `mx_x_admin_menu`
(`menuTitle`, `seoUri`, `parentID`, `xOrder`, `status`, `hideMenu`)
VALUES ('Events', 'events', 0, 50, 1, 0);

-- Get the adminMenuID (let's say it's 99)

-- 2. Create a new role for Event Managers
INSERT INTO `mx_x_admin_role`
(`roleName`, `roleEmail`, `rolePage`, `xOrder`, `status`)
VALUES ('Event Manager', NULL, 'events', 0, 1);

-- Get the roleID (let's say it's 16)

-- 3. Grant full access to the Events module for this role
INSERT INTO `mx_x_admin_role_access`
(`roleID`, `adminMenuID`, `accessType`, `status`)
VALUES (16, 99, '["view", "add", "edit", "delete"]', 1);

-- 4. Also grant Dashboard access so they can login
INSERT INTO `mx_x_admin_role_access`
(`roleID`, `adminMenuID`, `accessType`, `status`)
VALUES (16, 1, '["view"]', 1);  -- adminMenuID 1 = Dashboard

-- 5. Assign a user to this role
UPDATE `mx_x_admin_user` SET roleID = 16 WHERE userID = 25;
```

### 4.6 Special Roles

| roleID | Role Type | Access Level |
|--------|-----------|--------------|
| `SUPER` | Super Admin | Full access to ALL modules (hardcoded in `tpl.class.inc.php`) |
| `1` | Admin | Typically full access (configured via role_access) |
| `2+` | Custom Roles | Access based on `mx_x_admin_role_access` entries |

**Super Admin Check:**
```php
// In tpl.class.inc.php
if ($roleID == "SUPER") {
    // Grant access to ALL menus with ALL permissions
    foreach ($MXADMINMENU as $v) {
        $this->mAccess[$v["seoUri"]] = $MXACCESS;  // ['view','add','edit','delete']
    }
}
```

### 4.7 Hiding Menu Items

To hide a menu from sidebar but keep it accessible via direct URL:
```sql
UPDATE `mx_x_admin_menu` SET hideMenu = 1 WHERE seoUri = 'events';
```

To completely disable a menu:
```sql
UPDATE `mx_x_admin_menu` SET status = 0 WHERE seoUri = 'events';
```

### 4.8 Sub-Menu (Grouped Modules)

Create a parent menu and assign children:

```sql
-- Parent (no actual module, just a grouping header)
INSERT INTO `mx_x_admin_menu`
(`menuTitle`, `seoUri`, `parentID`, `xOrder`, `status`, `hideMenu`)
VALUES ('HR Management', 'hrms', 0, 60, 1, 0);

-- Get parent's adminMenuID (e.g., 100)

-- Children under HRMS
INSERT INTO `mx_x_admin_menu` VALUES
(NULL, 0, 'Attendance', 'attendance', 1, 100, '', 0, '', 1),
(NULL, 0, 'Salary Slip', 'salary-slip', 2, 100, '', 0, '', 1),
(NULL, 0, 'Leave Management', 'employee-leave', 3, 100, '', 0, '', 1);
```

**Result in sidebar:**
```
▼ HR Management
    ├── Attendance
    ├── Salary Slip
    └── Leave Management
```

---

## 5. ADVANCED FEATURES

### A. Sub-Tables (One-to-Many)
Use `$MXFRM->getFormG()` to handle details like product specifications.

```php
$arrSpecs = array(
    array("type" => "hidden", "name" => "specID"),
    array("type" => "text", "name" => "specLabel", "title" => "Label"),
    array("type" => "text", "name" => "specValue", "title" => "Value")
);
echo $MXFRM->getFormG(array("flds" => $arrSpecs, "vals" => $existingData, "type" => 0, "addDel" => true));
```
*   Requires handling in `x-{module}.inc.php` to loop through arrays and insert into child table.

### B. File Uploads
*   **Directory:** Ensure `uploads/{module_name}/` exists and is writable.
*   **Configuration:** In `setModVars`, map field names to folders:
    `"UDIR" => array("eventImage" => "events")`

### C. Validation
Add `validate` attribute to form fields:
*   `required`
*   `email`
*   `number`

---

**End of Guide**
