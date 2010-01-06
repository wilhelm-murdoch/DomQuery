<?php

require_once 'domquery.php';

ob_start();

?>
<root>
	<element>
		<child>This is a child ...</child>
	</element>
</root>
<?php

$original = ob_get_contents();

ob_end_clean();

ob_start();

?>
<root>
	<test><foo>test</foo></test>
	<omg>wow</omg>
</root>
<?php

$new = ob_get_contents();

ob_end_clean();

$Xml = new DomQuery;

$Xml->load($original)->merge($new, '//test', '//element');


header('Content-Type: text/xml');

die($Xml->saveXml());
