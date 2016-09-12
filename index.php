<?php
require "GPIO.php";//import the class
echo GPIO::pinout(); //printout what is the percieved pinout of the gpio header on the raspberry pi
echo "<br><br>";
echo GPIO::truthTableHTML(array(2, 1, 0), array(25, 24), 0);//generates an html truth table using pins 0, 1 and 2 as inputs to the circuit,
                                                            //and pins 25 and 24 as outputs from the circuit. 
                                                            //after writing an input set to the circuit, it will wait 0 seconds
                                                            //before reading the output.

//other example commands
//$pins = GPIO::status(); //gets an array of all gpio pin information
//GPIO::setMode(array(0, 1, 2),"out"); //sets gpio pins 0, 1 and 2 to be output pins
//GPIO::setMode(25, "in"); //sets gpio pin 25 to be an input pin
//GPIO::write(array(0, 1, 2), 1); //sets the output of gpio pins 0, 1 and 2 to 1(high)
//var_dump(GPIO::truthTable(array(0, 1, 2), array(25, 24), 0)); //generates a truth table using pins 0, 1 and 2 as inputs to the circuit, and pins 25 and 24 as outputs from the circuit. after writing an input set to the circuit, it will wait 0 seconds before reading the output
//GPIO::write(array(0, 1, 2), 0); //sets the output of gpio pins 0, 1 and 2 to 0(low)
//GPIO::reset();  //resets all gpio pins to their initial state at raspberry pi startup(mode "in", status 0)
?>
