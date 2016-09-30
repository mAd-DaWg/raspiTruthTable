<?php
//written by mAd-DaWg
//warning, use this code/class/library at your your own risk! mAd-DaWg assumes no responsibility for any damage resulting from its use.
//if used incorrectly with your hardware, you could damage your device!
//For example, if you set a pin to output a 1 and the pin is connected directly to ground(0v), it will cause a short circuit and permanently damage your raspberry pi
class GPIO
{
     /**
     * Get a 2 dimensional array of the current gpio pins status
     * @param int/array $pin Optional. specify a pin number or an array of pin numbers to only retrieve their status. leave false to retrieve everything. default is false
     * @param bool $gpio Optional. If true, explicitly uses gpio pins and their numbering(leaves out power pins etc). If false, uses physical pin numbering. default is true
     * @param bool $sort Optional. if true, it will sort the pins by pin number. if false, it will leave pins in the order they are found on the device. default is false
     * @return array (
     * 			pin number => array (
     *						GPIOpin => int,     //the gpio number of the pin if applicable.
     *						PhysicalPin => int, //the pin number as found on the raspberry pi hardware.
     *						BroadcomPin => int, //the cpu pin number that the pin is connected to.
     *						Name => string,     //the name that has been assigned to the pin.
     *						Mode => string,     //if the pin is in output mode or input mode.
     *						State => bool/int,  //if the pin is writing or reading a 1, this will be 1. if the pin is writing or reading a 0, this will be 0.
     *					    )
     *               )
     *
     **/
	static function status($pin = false, $gpio = true, $sort = true)
	{
		if($pin == null || $pin == "")
		{
			$pin = false;
		}
		$arr = explode("\n", shell_exec("gpio readall"));
		$pins = array();
		for($i = 3; $i < count($arr)-4; $i++)
		{
			$hold = explode(" || ", $arr[$i]);
			$hold1 = explode("|", $hold[1]);
			$hold = explode("|", $hold[0]);
			$pins[trim($hold[6])] = array("GPIOpin"=>trim($hold[2]), "PhysicalPin"=>trim($hold[6]), "BroadcomPin"=>trim($hold[1]), "Name"=>trim($hold[3]), "Mode"=>trim($hold[4]), "State"=>trim($hold[5]));
			$pins[trim($hold1[0])] = array("GPIOpin"=>trim($hold1[4]), "PhysicalPin"=>trim($hold1[0]), "BroadcomPin"=>trim($hold1[5]), "Name"=>trim($hold1[3]), "Mode"=>trim($hold1[2]), "State"=>trim($hold1[1]));
		}
		$gpios = array();
		foreach($pins as $pinz)
		{
			if(strpos($pinz['Name'], "GPIO") !== false)
			{
				$gpios[trim(explode(".", $pinz['Name'])[1])] = $pinz;
			}
		}
		$ans = array();
		if($gpio == true)
		{
			$ans = $gpios;
		}
		elseif($gpio == false)
		{
			$ans = $pins;
		}
		if($sort == true)
		{
			ksort($ans);
		}
		if($pin !== false)
		{
			if(is_array($pin))
			{
				$hold = array();
				foreach($pin as $p)
				{
					if(isset($ans[$p]) == true)
					{
						$hold[$p] = $ans[$p];
					}
					else
					{
						$hold[$p] = false;
					}
				}
				$ans = $hold;
			}
			else
			{
				if(isset($ans[$pin]) == true)
				{
					$ans = array($ans[$pin]);
				}
				else
				{
					$ans = false;
				}
			}
		}
		return $ans;
	}
	
     /**
     * set pins to input or output mode
     * @param int/array $pins Specify a pin number or an array of pin numbers you want to set. Note: these are all set to the same mode!
     * @param string $mode the mode to set the pins. can be either "in" or "out".
     * @param bool $gpio Optional. If true, explicitly uses gpio pins and their numbering(leaves out power pins etc). If false, uses physical pin numbering. default is true
     **/ 
	static function setMode($pins, $mode, $gpio = true)
	{
		$result = "";
		$gstat = array();
		if($gpio == true)
		{
			$gstat = GPIO::status($pins);
		}
		if(is_array($pins))
		{
			foreach($pins as $p)
			{
				if($gpio == true)
				{
					$p = $gstat[$p]['PhysicalPin'];
				}
				shell_exec("gpio -1 mode ".$p." ".$mode);
			}
		}
		else
		{
			if($gpio == true)
			{
				$pins = $gstat[$pins]['PhysicalPin'];
			}
			shell_exec("gpio -1 mode ".$pins." ".$mode);
		}
	}
	
     /**
     * set pins to input or output mode
     * @param int/array $pins Specify a pin number or an array of pin numbers you want to set. Note: will not work on pins that are not in "out" mode. these are all set to the same output!
     * @param int $data can be a 1 for high, or 0 for low
     * @param bool $gpio Optional. If true, explicitly uses gpio pins and their numbering(leaves out power pins etc). If false, uses physical pin numbering. default is true
     **/ 
	static function write($pins, $data, $gpio = true)
	{
		$gstat = array();
		if($gpio == true)
		{
			$gstat = GPIO::status($pins);
		}
		if(is_array($pins))
		{
			foreach($pins as $p)
			{
				if($gpio == true)
				{
					$p = $gstat[$p]['PhysicalPin'];
				}
				shell_exec("gpio -1 write ".$p." ".$data);
			}
		}
		else
		{
			if($gpio == true)
			{
				$pins = $gstat[$pins]['PhysicalPin'];
			}
			shell_exec("gpio -1 write ".$pins." ".$data);
		}
	}
	
     /**
     * returns all pins to 0 and input mode. WARNING: can destroy your logic circuit and possibly damage your rapsberry pi! disconnect any circuits you connected to your gpio pins before use!
     * @param int/array $pins Optional. Specify a pin number or an array of pin numbers you want to reset. If false, resets all pins
     * @param bool $gpio Optional. If true, explicitly uses gpio pins and their numbering(leaves out power pins etc). If false, uses physical pin numbering. default is true
     **/ 
	static function reset($pins = false, $gpio = true)
	{
		if($pins == null || $pins == "")
		{
			$pins = false;
		}
		$gstat = GPIO::status($pins, $gpio);
		if($pins == false)
		{
			foreach($gstat as $pin)
			{
				if($pin["Mode"] == "OUT")
				{
					GPIO::write($pin['PhysicalPin'], 0, false);
					GPIO::setMode($pin['PhysicalPin'], "in", false);
				}
			}
		}
		else
		{
			if(is_array($pins))
			{
				$out = array();
				foreach($pins as $p)
				{
					if($gpio == true)
					{
						$p1 = $gstat[$p]['PhysicalPin'];
						GPIO::write($p1, 0, false);
						GPIO::setMode($p1, "in", false);
					}
					else
					{
						GPIO::write($p, 0, false);
						GPIO::setMode($p, "in", false);
					}
				}
			}
			else
			{
				if($gpio == true)
				{
					$pin1 = $gstat[$pins]['PhysicalPin'];
					GPIO::write($pin1, 0, false);
					GPIO::setMode($pin1, "in", false);
				}
				else
				{
					GPIO::write($pins, 0, false);
					GPIO::setMode($pins, "in", false);
				}
			}
		}
	}
	
     /**
     * reads the current state of a pin
     * @param int/array $pins Specify a pin number or an array of pin numbers you want to read
     * @param bool $gpio Optional. If true, explicitly uses gpio pins and their numbering(leaves out power pins etc). If false, uses physical pin numbering. default is true
     * @return array(i have forgotten the output it gives if gpio is false. will have to look later)
     **/ 
	static function read($pins, $gpio = true)
	{
		$gstat = array();
		if($gpio == true)
		{
			$gstat = GPIO::status($pins);
		}
		if(is_array($pins))
		{
			$out = array();
			foreach($pins as $p)
			{
				if($gpio == true)
				{
					$out[$p] = $gstat[$p]['State'];
				}
				else
				{
					$out[$p] = shell_exec("gpio -1 read ".$p);
				}
			}
			return $out;
		}
		else
		{
			if($gpio == true)
			{
				return $gstat[$pins]['State'];
			}
			return shell_exec("gpio -1 read ".$pins);
		}
	}
	
     /**
     * generate a 2 dimensional array truth table for the specified input and output pins.
     * will return false if you have any overlapping write and read pins(1 pin can not be both for this test)
     * Note, this will change the pins modes and states for the purpose of this test and then restore the pins to the state they where in before the test
     * @param array $pinsWrite specify an array of pin numbers to be used as INPUTS to your circuit. the first pin
     * @param array $pinsRead specify an array of pin numbers to be used as OUTPUTS from your circuit
     * @param int   $sleep the amount of seconds to wait for a response from your circuit for each set of inputs.if you have a slow circuit, time/calculate how long it takes to function and then set this value to match. Defaults is 0.2 seconds
     * @param bool  $gpio Optional. If true, explicitly uses gpio pins and their numbering(leaves out power pins etc). If false, uses physical pin numbering. default is true
     * @return array (
     * 			inputset number => array (
     *						Write => array(
     * 								pin=> array (pin status array),
     * 								pin=> array (pin status array)
     * 							      ),
     *						Read => array(
     * 								pin=> array (pin status array),
     * 								pin=> array (pin status array)
     * 							      )
     *					    ),
     * 			inputset number => array (
     *						Write => array(
     * 								pin=> array (pin status array),
     * 								pin=> array (pin status array)
     * 							      ),
     *						Read => array(
     * 								pin=> array (pin status array),
     * 								pin=> array (pin status array)
     * 							      )
     *					    )
     *               )
     *
     **/
	static function truthTable($pinsWrite, $pinsRead, $sleep = 0.2, $gpio = true)
	{
		if(count($pinsWrite) > 0 && count($pinsRead) > 0)
		{
			for($i = 0; $i < count($pinsRead); $i++)//make sure we dont have overlaping pins
			{
				for($k = 0; $k < count($pinsWrite); $k++)
				{
					if($pinsRead[$i] == $pinsWrite[$k])
					{
						return false;
					}
				}
			}
			$orig = array_merge(GPIO::status($pinsRead, $gpio, false), GPIO::status($pinsWrite, $gpio, false));//store the original values of all the pins so we can restore them later
			$read = array_values(GPIO::status($pinsRead, $gpio, false));//get initial status and pin info
			$write = array_values(GPIO::status($pinsWrite, $gpio, false));//get initial status and pin info
			if($gpio == true)//for performance reasons, we use physical pin numbers only so that we only need to lookup each pins details once
			{
				for($i = 0; $i < count($pinsWrite); $i++)
				{
					$pinsWrite[$i] = $write[$i]['PhysicalPin'];
				}
				echo "\n";
				for($j = 0; $j < count($pinsRead); $j++)
				{
					$pinsRead[$j] = $read[$j]['PhysicalPin'];
				}
			}
			$read = null;
			$write = null;
			GPIO::reset($pinsWrite, false);
			GPIO::reset($pinsRead, false);
			GPIO::setMode($pinsWrite, "out", false);
			GPIO::setMode($pinsRead, "in", false);
			$table = array();
			$max = pow(2, count($pinsWrite));
			for($i = 0; $i < $max; $i++)
			{
				$hold = array_values(array_reverse(str_split(decbin($i))));//convert integer to binary;
				for($j = 0; $j < count($pinsWrite); $j++)
				{
					if(isset($hold[$j]) !== false)//write the corresponding value
					{
						GPIO::write($pinsWrite[$j], $hold[$j], false);
					}
					else//or fill in the zero's
					{
						GPIO::write($pinsWrite[$j], 0, false);
					}
				}
				sleep($sleep);
				$table[] = array("Write"=>GPIO::read($pinsWrite, false), "Read"=>GPIO::read($pinsRead, false));
			}
			foreach($orig as $p)
			{
				GPIO::write($p['PhysicalPin'], $p['State'], false);
				GPIO::setMode($p['PhysicalPin'], $p['Mode'], false);
			}
			return $table;
		}
	}

     /**
     * generate a little html truth table(note not an html table) for the specified input and output pins.
     * will return false if you have any overlapping write and read pins(1 pin can not be both for this test)
     * Note, this will change the pins modes and states for the purpose of this test and then restore the pins to the state they where in before the test
     * @param array $pinsWrite specify an array of pin numbers to be used as INPUTS to your circuit
     * @param array $pinsRead specify an array of pin numbers to be used as OUTPUTS from your circuit
     * @param int   $sleep the amount of seconds to wait for a response from your circuit for each set of inputs.if you have a slow circuit, time/calculate how long it takes to function and then set this value to match. Defaults is 0.2 seconds
     * @param bool  $gpio Optional. If true, explicitly uses gpio pins and their numbering(leaves out power pins etc). If false, uses physical pin numbering. default is true
     * @return string html code for the truth table
     **/
	static function truthTableHTML($pinsWrite, $pinsRead, $sleep = 0.2, $gpio = true)
	{
		$truths = GPIO::truthTable($pinsWrite, $pinsRead, $sleep, $gpio);
		if($truths !== false)
		{
			$out = "";
			foreach($pinsWrite as $pin)
			{
				$out .= $pin." ";
			}
			$out .= "| ";
			foreach($pinsRead as $pin)
			{
				$out .= $pin." ";
			}
			$out .= "<br>";
			foreach($pinsWrite as $pin)
			{
				$out .= "_ ";
				if ($pin > 9)
				{
					$out .= "_ ";
				}
			}
			$out .= "|";
			foreach($pinsRead as $pin)
			{
				$out .= "_ ";
				if ($pin > 9)
				{
					$out .= "_ ";
				}
			}
			$out .= "<br>";
			foreach($truths as $truth)
			{
				foreach($truth['Write'] as $pin)
				{
					$out .= $pin." ";
				}
				$out .= "| ";
				foreach($truth['Read'] as $pin)
				{
					$out .= $pin." ";
				}
				$out .= "<br>";
			}
			return $out;
		}
		return "something went wrong. please make sure your pins are correct!";
	}
	
     /**
     * generate a html table detailing the layout of all the pins found on the raspberry pi(layout should be is as it is found on your hardware, according to wiring pi)
     * @return string html code
     **/
	static function pinout()
	{
		$pins = GPIO::status(null, false, true);
		$out = "<table style=\"float: left;\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\">
<tbody>
<th>
<td><strong>Name</strong></td>
<td><strong>Mode</strong></td>
<td><strong>State</strong></td>
<td><strong>Pin</strong></td>
<td><strong>Pin</strong></td>
<td><strong>Name</strong></td>
<td><strong>Mode</strong></td>
<td><strong>State</strong></td>
</th>";
		for($i = 1; $i < 40; $i = $i+2)
		{
			$out .= "<tr>\n<td style=\"text-align: left;\">".$pins[$i]['Name']."</td>
			<td style=\"text-align: left;\">";
			if($pins[$i]['Mode'] == "IN")
			{
				$out .= "<span style=\"color: #008000;\">";
			}
			elseif($pins[$i]['Mode'] == "OUT")
			{
				$out .= "<span style=\"color: #ff0000;\">";
			}
			$out .= $pins[$i]['Mode']."</span></td>";
			$out .= "<td style=\"text-align: left;\">".$pins[$i]['State']."</td>";
			$out .= "<td><strong>".$i."</strong></td>";
			$out .= "<td><strong>".($i+1)."</strong></td>";
			$out .= "<td style=\"text-align: left;\">".$pins[$i+1]['Name']."</td>";
			$out .= "<td style=\"text-align: left;\">";
			if($pins[$i+1]['Mode'] == "IN")
			{
				$out .= "<span style=\"color: #008000;\">";
			}
			elseif($pins[$i+1]['Mode'] == "OUT")
			{
				$out .= "<span style=\"color: #ff0000;\">";
			}
			$out .= $pins[$i+1]['Mode']."</span></td>
<td style=\"text-align: left;\">".$pins[$i+1]['State']."</td></tr>";
		}
		$out .= "</tbody></table>";
		return $out;
	}
	
     /**
     * generate a html form for selecting the mode of pins
     * @param string $name the name and id of the form
     * @param string $action the form action on submit
     * @param string $method whether to post the form using get or post
     * @param bool   $select add an extra column to enable selecting of pins
     * @return string html code
     **/
	static function InOutForm($name, $action, $method = "post", $select=false)
	{
		$pins = GPIO::status(null, false, true);
		$out = "<form name='".$name."' id='".$name."' action='".$action."' method='".$method."'><table style=\"float: left;\" border=\"1\" cellpadding=\"0\" cellspacing=\"0\">
<tbody>
<th>";
if($select == true){$out .= "\n<td><strong>Selected</strong></td>";}
$out .= "
<td><strong>Name</strong></td>
<td><strong>Mode</strong></td>
<td><strong>State</strong></td>
<td><strong>Pin</strong></td>
<td><strong>Pin</strong></td>
<td><strong>Name</strong></td>
<td><strong>Mode</strong></td>
<td><strong>State</strong></td>";
if($select == true){$out .= "\n<td><strong>Selected</strong></td>";}
$out .= "
</th>";
		for($i = 1; $i < 40; $i = $i+2)
		{
			$out .= "<tr>\n";
			if($select == true)
			{
				$out .= "<td><input type='checkbox' name='pin".$pins[$i]['PhysicalPin']."' value='1'></td>";
			}
			$out .= "<td style=\"text-align: left;\">".$pins[$i]['Name']."</td>
			<td style=\"text-align: left;\">IN<input type='radio' name='pin".$pins[$i]['PhysicalPin']."' value='in'";
			if($pins[$i]['Mode'] == "IN")
			{
				$out .= "checked";
			}
			$out .=  "> OUT<input type='radio' name='pin".$pins[$i]['PhysicalPin']."' value='out'";
			elseif($pins[$i]['Mode'] == "OUT")
			{
				$out .= "checked";
			}
			$out .=  "></td>";
			$out .= "<td style=\"text-align: left;\">".$pins[$i]['State']."</td>";
			$out .= "<td><strong>".$i."</strong></td>";
			$out .= "<td><strong>".($i+1)."</strong></td>";
			$out .= "<td style=\"text-align: left;\">".$pins[$i+1]['Name']."</td>";
			$out .= "<td style=\"text-align: left;\">IN<input type='radio' name='pin".$pins[$i+1]['PhysicalPin']."' value='in'";
			if($pins[$i+1]['Mode'] == "IN")
			{
				$out .= "checked";
			}
			$out .=  "> OUT<input type='radio' name='pin".$pins[$i+1]['PhysicalPin']."' value='out'";
			elseif($pins[$i+1]['Mode'] == "OUT")
			{
				$out .= "checked";
			}
			$out .=  "></td>
			<td style=\"text-align: left;\">".$pins[$i+1]['State']."</td>";
			if($select == true)
			{
				$out .= "<td><input type='checkbox' name='pin".$pins[$i]['PhysicalPin']."' value='1'></td>";
			} 
			$out .= "</tr>";
		}
		$out .= "</tbody></table><input type='submit' value='Submit'></form>";
		return $out;
	}
}
?>
