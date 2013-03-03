<?php

include("redirect.php");
include("includes/db_lib.php");
include("includes/header.php"); 

// Load all laboratories that will be seen for this particular page.

$director_id = $_SESSION['user_id'];

$lab_array = get_labs_for_director($director_id);

$country_name = get_country_from_user_id();

$labs = array();

foreach ($lab_array as $lab)
{
	$lab_id = $lab["lab_config_id"];
	$lab_name = get_lab_name_by_id_revamp($lab_id);
	$coords = is_lab_placed($lab_id, $director_id);
	$labs[$lab_name[0]["name"]] = array($lab_id, $lab_name[0]["name"], $coords);
}

?>
<html>
<head>
	<script src="js/raphael-min.js"></script>
	<script src="js/jquery-1.3.2.min.js"></script>

	<script type="text/javascript">
	function getRandomColorHex()
	{
		var num = Math.floor(Math.random() * 16777215).toString(16);
		var pad = "000000";
		num = pad.substring(0, pad.length - num.length) + num;
		return '#' + num;
	}
	
	$(document).ready(function() {
		// Start drawing the Map.
		
		<?php
		$country_name = "ghana";
		include($country_name . "_svg");
		?>

		$("#map_title").text(map_text); // Sets map title
		
		var label = rsr.text(650, 40, "Unplaced Labs").attr({
			"font-size": 20,
			"text-anchor": 'start'
		});

		var box = rsr.rect(625, 60, 250, 50 + (50 * <?php echo count($labs) - 1; ?>)).attr({
			fill: "none",
			stroke: "black"
		});
		
		<?php
		// Create a circle and drag handler for each lab already set.  Place them at the appropriate coords.
		$i = -1;
		foreach ($labs as $lab)
		{
			$coords = $lab[2];
			if (isset($coords))
			{
				?>var placed = 1;<?php
				$exploded_coords = explode(",", $coords);
				$xCoord = trim(str_replace("(", "", $exploded_coords[0]));
					$yCoord = trim(str_replace(")", "", $exploded_coords[1]));
				} else
				{
					?>var placed = 0;<?php
					$i++;
				}
				?>
			//Get a random color for the circle.
			var mycolor = getRandomColorHex()

			//Create the circle.
			if (placed == 1)
			{
				var circ<?php echo $lab[0]; ?> = rsr.circle(<?php echo $xCoord; ?>, <?php echo $yCoord; ?>, 20).attr({
					fill: mycolor,
					stroke: "black",
					"fill-opacity": 0.5
				});
			} else
			{
				var circ<?php echo $lab[0]; ?> = rsr.circle(650, 95 + (45 * <?php echo $i; ?>), 20).attr({
					fill: mycolor,
					stroke: "black",
					"fill-opacity": 0.5
				});
			}
			
			//Label for the lab circle.
			
			if (placed == 1)
			{
				var txt<?php echo $lab[0]; ?> = rsr.text(<?php echo $xCoord; ?> + 15, <?php echo $yCoord; ?> - 25, "<?php echo $lab[1]; ?>").attr({
					"font-size": 15,
					"text-anchor": 'start'
				});
			} else
			{
				var txt<?php echo $lab[0]; ?> = rsr.text(665, 70 + (45 * <?php echo $i; ?>), "<?php echo $lab[1]; ?>").attr({
					"font-size": 15,
					"text-anchor": 'start'
				});
			}
			
			//Place a dot in the center of the circle
			if (placed == 1)
			{
				var dot<?php echo $lab[0]; ?> = rsr.circle(<?php echo $xCoord; ?>, <?php echo $yCoord; ?>, 1).attr({
					fill: "black"
				});
			} else
			{
				var dot<?php echo $lab[0]; ?> = rsr.circle(650, 95 + (45 * <?php echo $i; ?>), 1).attr({
					fill: "black"
				});
			}
			
			//Link the circles and text.
			var set<?php echo $lab[0]; ?> = rsr.set();
			set<?php echo $lab[0]; ?>.push(circ<?php echo $lab[0]; ?>);
			set<?php echo $lab[0]; ?>.push(txt<?php echo $lab[0]; ?>);
			set<?php echo $lab[0]; ?>.push(dot<?php echo $lab[0]; ?>);
			
			//Movement functions for the circles
			circ<?php echo $lab[0]; ?>_start = function () {
				this.ox = this.attr("cx");
				this.oy = this.attr("cy");
			}
			
			circ<?php echo $lab[0]; ?>_move = function (dx, dy) {
				set<?php echo $lab[0]; ?>.attr({cx: this.ox + dx});
				set<?php echo $lab[0]; ?>.attr({cy: this.oy + dy});
				set<?php echo $lab[0]; ?>.attr({x: this.ox + dx + 15});
				set<?php echo $lab[0]; ?>.attr({y: this.oy + dy - 25});
			}
			
			circ<?php echo $lab[0]; ?>_up = function () {
				var xVal = this.attr("cx");
				var yVal = this.attr("cy");
				//	if the circle has been dragged onto the map, we want to hide the text from the list and collapse the new empty space.
				if (xVal < 650 && yVal < 600 && xVal > 0 & yVal > 0)
				{
					var dataObj = {};
					
					dataObj['xCoord'] = xVal;
					dataObj['yCoord'] = yVal;
					dataObj['labId'] = <?php echo $lab[0]; ?>;
					dataObj['directorId'] = <?php echo $director_id; ?>;

					$('#update_coord_progress').show();
					
					//Save the position for this lab.
					$.ajax({
						url: "update_lab_coords.php",
						type: "POST",
						data: dataObj,
						success: function(data) {
							$('#update_coord_progress').hide();
							$('#update_coord_success').animate({opacity: 1}, 0);
							setTimeout(function() {
								$('#update_coord_success').animate({opacity: 0}, 500);
							}, 1500);	
						},
						failure: function(data) {
							$('#update_coord_progress').hide();
							$('#update_coord_failure').animate({opacity: 1}, 0);
							setTimeout(function() {
								$('#update_coord_failure').animate({opacity: 0}, 1000);
							}, 3000);
						}
					});

					
				} else
				{
					//If they didn't place the circle on the map, we snap the circle back to its original position.
					set<?php echo $lab[0]; ?>.attr({cx: this.ox});
					set<?php echo $lab[0]; ?>.attr({cy: this.oy});
					set<?php echo $lab[0]; ?>.attr({x: this.ox + 15});
					set<?php echo $lab[0]; ?>.attr({y: this.oy - 25});
				}
			}
			
			//Create drag handler for the circle.
			circ<?php echo $lab[0]; ?>.drag(circ<?php echo $lab[0]; ?>_move, circ<?php echo $lab[0]; ?>_start, circ<?php echo $lab[0]; ?>_up);
			<?php
		}
		?>
	});

</script>
</head>
<body>
	<span id='map_title'>
	</span>
	<br />
	<span id='update_coord_progress' style='display:none;'>
		<?php $page_elems->getProgressSpinner(LangUtil::$generalTerms['CMD_SUBMITTING']); ?>
	</span>
	<span id='update_coord_success' style='opacity:0;'>
		Location Updated!
	</span>
	<span id='update_coord_failure' style='opacity:0;'>
		Location Not Updated.  Click or drag the circle again to retry!
	</span>

	<br />
	<div id='rsr' style=" float: inherit"></div>
	<div id="lab_box">
	</div>
</body>
</html>

<?php

include("includes/footer.php");

?>