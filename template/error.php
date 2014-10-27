<?php
/**
 * The default error page template for the Sleepy web framework.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */

// We choose our display language based on user preferrences.
// We do this by parsing the HTTP Accept-Language variable.

$lang = 'en';
if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$tmp = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
	if (!empty($tmp)) {
		foreach ($tmp as $lng) {
			$s = explode(';q=', $lng);
			$w = (isset($s[1])) ? ($s[1] * 100) : 100;
			$lst[$w] = trim($s[0]);
		}

		krsort($lst);
		$lang = reset($lst);
	}

	$lang = strtolower(preg_replace('/_\s/', '-', $lang));
	$lang = reset(explode('-', $lang));
}

switch ($lang) {
case 'el':
	$t['home'] = 'Αρχική';
	$t['go-back'] = 'Πίσω';
	$t['try-again'] = 'Δοκιμή ξανά';
	switch ($resp['code']) {
	case 404:
		$t['heading'] = 'Δεν βρέθηκε';
		$t['message'] = 'Η σελίδα που ζητήσατε δεν βρέθηκε.';
		break;
	default:
		$t['heading'] = 'Παρουσιάστηκε πρόβλημα';
		$t['message'] = 'Ο διακομιστής απέτυχε κατά την εξυπηρέτηση του αιτήματος σας.';
	}
	break;
case 'en':
default:
	$t['home'] = 'Home';
	$t['go-back'] = 'Go Back';
	$t['try-again'] = 'Try Again';
	switch ($resp['code']) {
	case 404:
		$t['heading'] = 'Not found';
		$t['message'] = 'Sorry, but the page you were trying to view does not exist.';
		break;
	default:
		$t['heading'] = 'An error has occurred';
		$t['message'] = 'The server failed to complete the request';
	}
}

?>
<!DOCTYPE html>
<html lang="<?php echo $lang ?>">
	<head>
		<meta charset="utf-8">
		<title><?php echo $t['heading'] ?> :(</title>
		<style>
			::-moz-selection {
				background: #b3d4fc;
				text-shadow: none;
			}

			::selection {
				background: #b3d4fc;
				text-shadow: none;
			}

			html {
				height: 100%;
				font-size: 20px;
				line-height: 1.4;
				color: #737373;
				background: #f0f0f0 url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADIAAAAyCAMAAAAp4XiDAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAEtQTFRFjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mjo+Mju7hoQAAABl0Uk5TAAECAwQFBgcICQoLDA0OEBESExQVFhcZGtS5Hb4AAAFXSURBVEjH1dTBboQgEIDhfwZQEEVd123f/0l7cN2wVUzbg7GTzOm/AAkf6RHZnWLg4/MBQwfdAOEmuLuidyfFQDtHqB24GowH9YIE3QlhCWfNUEOKEBPUA9hR0dEig8+DvAK0DhoPvgHXgukE6cxBOGm6CqIHH6HqwCRBk3mG8B4kGaB/Xr9JUPdgB0UHi5TCWRMs+AoqDzaANoI0ehC4eehbaHvwo+AmRSeH3Dz03TacNU7BGjAO1IFUglRyEJhC9pOm5SeZuUKmAEPKwqyY2QEqIOsq8NpS+MsYAdVlxSyLkYPAPcCYoBvzI1fIfb3LtwB2fRgLakEc4OQgIKUTF0Oa47PK+xbDQl+fCzdl9PU/oy9ckb71i48WeQ+79MUSffE69MmLPn9EX7wifXUuXMjo24RwTF+4CH22RJ/dpS8d0bfhQjPd9unTf0Pf5mFW4Yrh9/R9AWqxGIl05IuAAAAAAElFTkSuQmCC) repeat 0 0;
				-webkit-text-size-adjust: 100%;
				-ms-text-size-adjust: 100%;
			}

			@media (min-width: 768px) and (max-width: 979px) {
				html {
					font-size: 18px;
				}
			}

			@media (max-width: 767px) {
				html {
					font-size: 16px;
				}
			}

			body {
				margin: 0;
				padding: 0;
				height: 100%;
			}

			html,
			input {
				font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
			}

			h1 {
				font-size: 3em;
				margin: 0 0 10px 0;
				position: relative;
			}

			h1 span {
				color: #bbb;
			}

			h3 {
				margin: 1.5em 0 0.5em;
			}

			p {
				margin: 1em 0;
			}

			ul {
				padding: 0 0 0 40px;
				margin: 1em 0;
			}

			a {
				color: #444;
			}

			.container {
				margin-top: -150px;
				position: relative;
				text-align: center;
				top: 50%;
			}

			.response {
				color: #888;
				font-size: 0.9em;
			}

			.button {
				display: inline-block;
				width: 120px;
			}

			.button a {
				color: #737373;
				outline: none;
				text-decoration: none;
			}

			.button .icon {
				cursor: pointer;
				display: block;
				font-size: 3em;
				font-style: normal;
				margin: 0 auto -15px;
				-webkit-transition: -webkit-transform .2s ease;
				-moz-transition: -moz-transform .2s ease;
				-ms-transition: -ms-transform .2s ease;
				-o-transition: -o-transform .2s ease;
				transition: transform .2s ease;
			}

			.button .descr {
				font-size: 0.8em;
				opacity: 0;
				position: relative;
				bottom: 10px;
				white-space: nowrap;
				-webkit-transition: opacity .3s ease, bottom .3s ease;
				-moz-transition: opacity .3s ease, bottom .3s ease;
				-ms-transition: opacity .3s ease, bottom .3s ease;
				-o-transition: opacity .3s ease, bottom .3s ease;
				transition: opacity .3s ease, bottom .3s ease;
			}

			.button .icon:hover + .descr {
				opacity: 1;
				bottom: 0;
			}
		</style>
	</head>
	<body>
		<div class="container">
			<?php
			echo '<h1>'.$t['heading'].' <span>:(</span></h1>
			<p class="response">('.$resp['code'].' '.$resp['text'].')</p>
			<p>'.$t['message'].'</p>
			
			<span class="button back">
				<a href="/" class="icon">↶</a>
				<span class="descr">'.$t['home'].'</span>
			</span>

			<span class="button back">
				<i class="icon" onclick="history.back(-1)">↖</i>
				<span class="descr">'.$t['go-back'].'</span>
			</span>

			<span class="button refresh">
				<i class="icon" onclick="document.location.reload(true)">↺</i>
				<span class="descr">'.$t['try-again'].'</span>
			</span>';
			?>

		</div>
	</body>
</html>
