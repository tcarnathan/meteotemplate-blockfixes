<?php

	# 		Warnings United States - Regional
	# 		Namespace:		warningsUSRegions
	#		Meteotemplate Block

	# 		v1.1 - Jan 29, 2016
	#			- bug fixes
	#		v2.0 - Jul 08, 2016
	#			- added possibility to view details about the individual warnings
	#		v3.0 - Aug 23, 2016
	#			- added possibility to use multiple locations
	#		v4.0 - Jan 10, 2017
	#			- added possibility to auto open warnings
	#		v4.1 - Feb 10, 2017
	#			- fixes to make compatible with https
	#		v5.0 - Feb 13, 2017
	#			- fixes to make compatible with https
	# 		v6.0 - Feb 16, 2017 
	# 			- redesign
	# 			- added further customizations
	# 			- bug fixes
	# 		v7.0 - Mar 30, 2018
	# 			- optimization
			v7.01 Fixed issue with it not updating

	if(file_exists("settings.php")){
		include("settings.php");
	}
	else{
		echo "Please go to your admin section and go through the settings for this block first.";
		die();
	}	

	if(!is_dir("cache")){
		mkdir("cache");
	}

	$warningsStateUS = str_replace(" ","",$warningsStateUS); // remove spaces if user included them
	$warningLocations = explode(",",$warningsStateUS);

	// load theme
	$designTheme = json_decode(file_get_contents("../../css/theme.txt"),true);
	$theme = $designTheme['theme'];

	include("../../../config.php");
	include("../../../scripts/functions.php");

	$language = loadLangs();

	$output = array();


	if(file_exists("cache/warningsUSRegions.txt")){
		if (time()-filemtime("cache/warningsUSRegions.txt") > 60 * $warningsCache) {
			unlink("cache/warningsUSRegions.txt");
		}
	}
	if(file_exists("cache/warningsUSRegions.txt")){
		$output = json_decode(file_get_contents("cache/warningsUSRegions.txt"),true);
	}
	else {
		$warnings = array();
		foreach($warningLocations as $location){
			$urlWarningsUS = "https://api.weather.gov/alerts/active.atom?zone=".$location."";
			$warningsUSRaw = curlMain($urlWarningsUS,16);
			$warningsUSRaw = str_replace("&","&amp;",$warningsUSRaw);
			$xmlWarningsUS = simplexml_load_string($warningsUSRaw);
			$name = $xmlWarningsUS->title;
			$name = str_replace("Current Watches, Warnings and Advisories for ","",$name);
			$name = str_replace("Issued by the National Weather Service","",$name);
			foreach($xmlWarningsUS->entry as $value) {
				$current['name'] = $name;
				$current['title'] = $value->title;
				$current['published'] = $value->published;
				$current['summary'] = str_replace("...","<br>",$value->summary);
				$current['summary'] = ltrim($current['summary'],"<br>");
				$current['summary'] = (string)$value->summary;
				$capFields = $value->children('cap', true);
				$current['category'] = $capFields->category;
				$current['id'] = (string)$value->id;
				$current['event'] = $capFields->event;
				$current['severity'] = $capFields->severity;
				$current['urgency'] = $capFields->urgency;
				$current['areas'] = $capFields->areaDesc;
				$current['certainty'] = $capFields->certainty;
				$current['msgType'] = $capFields->msgType;
				$current['status'] = $capFields->status;
				$current['effective'] = str_replace("T","  ",$capFields->effective);
				$current['effective'] = substr($current['effective'],0,16);
				$current['expires'] = str_replace("T","  ",$capFields->expires);
#				$current['expires'] = substr($current['expires'],0,count($current['expires'])-7);
				if($current['title']!="There are no active watches, warnings or advisories"){
					// get full desc 
					$urlFull = (string)$current['id'];
					$fullDescRaw = curlMain($urlFull,16);
					preg_match("/<description>(.*)<\/description>/s",$fullDescRaw,$matches);
					if(isset($matches[1])){
						$current['summary'] = $matches[1];
						$current['summary'] = str_replace("...","<br>",$current['summary']);
						$current['summary'] = str_replace("*","<br><br>*",$current['summary']);
					}
					preg_match("/<instruction>(.*)<\/instruction>/s",$fullDescRaw,$matches);
					if(isset($matches[1])){
						$current['instruction'] = $matches[1];
						$current['instruction'] = str_replace("..."," ",$current['instruction']);
					}
					$warnings[] = $current;
				}
			}
			if(count($warnings)>0){
				$output[$location] = $warnings;
			}
			$warnings = array(); // reset array
		}
		file_put_contents("cache/warningsUSRegions.txt",json_encode($output));
	}

	if(!$autoOpenWarnings){
		$textInitial = lang('more','c');
	}
	else{
		$textInitial = lang('hide','c');
	}
?>
	<style>
		.usWarningsRegionalRow{
			cursor: pointer;
			opacity: 0.85;
		}
		.usWarningsRegionalRow:hover{
			opacity:1;
		}
	</style>
	<?php 
		if($showWarningsImage){
	?>
		<span class="mticon-warninggeneral" style="font-size:1.6em"></span><br>
	<?php 
		}
	?>
	<?php
		if(count($output)==0){
	?>
			<span style="font-size:1.3em;color:#59B300;font-variant:small-caps" class="shadow">
				<?php echo $textNoWarnings?>  
			</span>
	<?php
		}
		else {
	?>
			<div class="shadow" style="font-size:1.3em;color:#D90000;font-variant:small-caps">
				<?php echo $textWarnings?>
			</div>
			<div style="width:98%;margin:0 auto;margin-top:3px;" id="warningsUSDetailRegional" class="details">
			<?php
				foreach($output as $key=>$location){
			?>
					<div style='font-variant:small-caps;margin:0 auto;margin-top:5px'><?php echo $location[0]['name']?></div>
					<?php
						for($i=0;$i<count($location);$i++){
							if($location[$i]['severity'][0]=="Minor"){
								$warningColor = "#FFFF99";
							}
							else if($location[$i]['severity'][0]=="Moderate"){
								$warningColor = "#FFB973";
							}
							else if($location[$i]['severity'][0]=="Severe"){
								$warningColor = "#FF9673";
							}
							else if($location[$i]['severity'][0]=="Extreme"){
								$warningColor = "#FF4C4C";
							}
							else{
								$warningColor = "#BFFFBF";
							}
							preg_match("/^(.*?) issued (.*?) by NWS/",$location[$i]['title'][0],$matches);
					?>
								<div id="warningsUSRegionsOpener<?php echo ($key.$i)?>" class="usWarningsRegionalRow" style="width:98%;color:black;text-align:center;border-bottom:1px solid black;background:<?php echo $warningColor?>;margin:0 auto;padding-top:2px;padding-bottom:2px;border-radius:5px;margin-top:2px">
									<?php 
										if(!isset($matches[2])){
											echo $location[$i]['title'][0];
										}
										else{
									?>
										<span style="font-variant:small-caps;font-size:1.1em;font-weight:bold">
											<?php echo $matches[1];?>
										</span>
										<br>
										<span style="font-size:0.8em">
											<?php echo $matches[2];?>
										</span>
									<?php
										}
									?>
								</div>
					<?php
						}
					?>
					
				<?php
					}
				?>
				</div>
				<div style='width:98%;margin:0 auto;text-align:center;font-size:0.7em;font-variant:small-caps;padding-top:10px'>
					Data source: NWS
				</div>
				<span id="warningsUSDetailMoreOption" class="more" onclick="txt = $('#warningsUSDetailRegional').is(':visible') ? '<?php echo lang('more','l')?>' : '<?php echo lang('hide','l')?>';$('#warningsUSDetailRegional').slideToggle(800);$(this).text(txt)">
					<?php echo $textInitial?>
				</span>
				<?php
					foreach($output as $key=>$location){
						for($i=0;$i<count($location);$i++){
				?>
							<div id="warningsUSRegionsWarning<?php echo ($key.$i)?>" title="Current Advisory/Watch/Warning Info">
								<h2><?php echo $location[$i]['event'][0]?></h2>
								<br>
								<table>
									<tr>
										<td style="text-align:left;padding-left:3px;font-variant:small-caps;font-size:1.1em;font-weight:bold">
											Areas
										</td>
										<td style="text-align:left;padding-left:10px">
											<?php echo $location[$i]['areas'][0]?>
										</td>
									</tr>
									<tr>
										<td style="text-align:left;padding-left:3px;font-variant:small-caps;font-size:1.1em;font-weight:bold">
											Severity
										</td>
										<td style="text-align:left;padding-left:10px">
											<?php echo $location[$i]['severity'][0]?>
										</td>
									</tr>
									<tr>
										<td style="text-align:left;padding-left:3px;font-variant:small-caps;font-size:1.1em;font-weight:bold">
											Certainty
										</td>
										<td style="text-align:left;padding-left:10px">
											<?php echo $location[$i]['certainty'][0]?>
										</td>
									</tr>
									<tr>
										<td style="text-align:left;padding-left:3px;font-variant:small-caps;font-size:1.1em;font-weight:bold">
											Urgency
										</td>
										<td style="text-align:left;padding-left:10px">
											<?php echo $location[$i]['urgency'][0]?>
										</td>
									</tr>
								<tr>
									<td style="text-align:left;padding-left:3px;font-variant:small-caps;font-size:1.1em;font-weight:bold">
										Status
									</td>
									<td style="text-align:left;padding-left:10px">
										<?php echo $location[$i]['status'][0]?>
									</td>
								</tr>
								<tr>
									<td style="text-align:left;padding-left:3px;font-variant:small-caps;font-size:1.1em;font-weight:bold">
										Effective
									</td>
									<td style="text-align:left;padding-left:10px">
										<?php echo $location[$i]['effective']?>
									</td>
								</tr>
							</table>
							<br>
							<div style="width:98%;margin:0 auto;font-size: 0.8em;padding:10px">
								<?php echo $location[$i]['summary'];?>
							</div>
							<?php 
								if(isset($location[$i]['instruction'])){
									if($location[$i]['instruction']!=""){
							?>
										<div style="width:98%;margin:0 auto;font-size: 0.8em;padding:10px">
											<strong>Instruction:</strong><br><?php echo $location[$i]['instruction'];?>
										</div>
							<?php
									}
								}
							?>
						</div>
						<script>
							dialogHeight = screen.height*0.5;
							dialogWidth = screen.width*0.5;
							$("#warningsUSRegionsWarning<?php echo ($key.$i)?>").dialog({
								modal: true,
								autoOpen: false,
								height: dialogHeight,
								width: dialogWidth
							});
							$("#warningsUSRegionsOpener<?php echo ($key.$i)?>").click(function(){
								$("#warningsUSRegionsWarning<?php echo ($key.$i)?>").dialog('open');
							})
						</script>
				<?php
				}
			} 
		}
	?>
	<script>
		<?php
			if(count($output)!=0 && $autoOpenWarnings){
		?>

				$('#warningsUSDetailRegional').slideToggle(800);
				$("#warningsUSDetailMoreOption").text(<?php echo lang('hide','l')?>)
		<?php
			}
		?>
	</script>
