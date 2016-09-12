<?php
class GPIO
{
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
	
	static function truthTableHTML($pinsWrite, $pinsRead, $sleep = 0.2, $gpio = true)
	{
		$truths = GPIO::truthTable($pinsWrite, $pinsRead, $sleep, $gpio);
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
}
?>