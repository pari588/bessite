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

## 4. REGISTERING THE MODULE

To make the module appear in the sidebar, you must insert a record into the `mx_admin_menu` table.

**SQL:**
```sql
INSERT INTO `mx_admin_menu` 
(`menuTitle`, `seoUri`, `parentID`, `xOrder`, `status`, `hideMenu`) 
VALUES 
('Events', 'events', 0, 99, 1, 0);
```

*   `seoUri`: Must match the directory name (`events`).
*   `parentID`: 0 for top-level, or the ID of a parent menu item.

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
