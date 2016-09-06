<?php

	include(dirname(dirname(__FILE__)).'/settings.php');

	$data = array(
		'benneywatches.net',
		'carltonbigband.co.uk',
		'davismotoringschool.com',
		'daysouttogether.com',
		'equinox-tech.co.uk',
		'exeter-leadership-consulting.co.uk',
		'fuse-server.com',
		'getgoingwithbowen.co.uk',
		'interfaceuganda.org',
		'jclifechanger.co.uk',
		'kernowcampervans.co.uk',
		'landandseaagency.com',
		'logiscan.co.uk',
		'mayflower400uk.com',
		'mccaren-aia.co.uk',
		'mortgagesfromcmb.co.uk',
		'myrfhosting.com',
		'norbord.co.uk',
		'printcopyscan.co.uk',
		'pushed.co.uk',
		'rfdigitalhosting.com',
		'securiguardgroup.co.uk',
		'smirthwaite.com',
		'stmellionflowers.co.uk',
		'tamarsecurity.co.uk',
		'ukdmc.org'
	);

	// pre_r($data);

	$file = fopen("domain-list.csv", "w");


	fputcsv($file, array("Domain", "Host", "IP Address"));

	foreach($data as $datum){

		$line = array(
			$datum,
			getHostNameByIp(gethostbyname($datum)),
			gethostbyname($datum)
		);

		fputcsv($file, $line);
	}

	function getHostNameByIp($ip){

		switch ($ip) {
			case '212.48.74.143':
				# code...
				return 'Heart Internet';

			case '185.116.214.8':
				return 'Smart Hosting';

			case '162.13.203.192':
				return 'DP1';

			case '216.59.63.91':
				return 'DP1 WAF?';

			case '77.104.131.149':
				return 'Site Ground';

			case '37.128.188.37':
				return 'Flint old server';

			case '185.38.38.146':
				return 'Flint new server';

			default:
				return 'IP Unknown';
		}
	}