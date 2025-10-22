<?php
echo "Document root: " . $_SERVER["DOCUMENT_ROOT"] . "<br>";
echo "Root path from config: " . $_SERVER["DOCUMENT_ROOT"] . "" . "<br>";
echo "Current script directory: " . dirname(__FILE__);
?>