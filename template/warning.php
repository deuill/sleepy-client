<?php
/**
 * The default warning template for the Sleepy web framework.
 *
 * Sleepy, the lightweight web application framework.
 * Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 *
 * @copyright	Copyright 2012 - 2014, Alex Palaistras (http://deuill.org)
 * @link		http://deuill.org/sleepy
 * @package		Sleepy.Core
 * @since		Sleepy 0.1.0
 */

echo '
<div style="background-color: #B94A48; border: 1px solid #993C3A; border-radius: 2px; color: #FFF; line-height: 1; margin: 10px; padding: 8px 30px 8px 12px; position: relative;">
	<span style="font-size: 24px; color: #fff; cursor: pointer; position: absolute; top: 4px; right: 9px" title="Close" onclick="this.parentNode.parentNode.removeChild(this.parentNode)">&times;</span>
	<span style="font-size: 14px; font-weight: bold">'.$severity.'</span>
	<span style="font-size: 13px">'.$message.', in file <b>'.$path.'</b>, line <b>'.$line.'</b></span>
</div>
';

/* End of file warning.php */