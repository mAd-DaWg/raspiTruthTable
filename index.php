<?php
require "GPIO.php";
$pins = GPIO::status();
echo GPIO::pinout();
echo "<br><br>";
echo GPIO::truthTableHTML(array(2, 1, 0), array(25, 24), 0);
//GPIO::setMode(array(0, 1, 2),"out");
//GPIO::setMode(25, "in");
//GPIO::write(array(0, 1, 2), 1);
//var_dump(GPIO::truthTable(array(0, 1, 2), array(25, 24), 0));
//GPIO::write(array(0, 1, 2), 0);
//GPIO::reset();
?>