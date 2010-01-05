<?php

// More demos to come, this is just a place holder. :)

require_once 'domquery.php';

ob_start();

?>
<root>
	<item>Item One</item>
	<item>Item Two</item>
	<item test="omg">Item Three</item>
	<item>Item Four</item>
	<item>Item Five</item>
	<parent>
		<child>omg</child>
		<child>
			<test/>
		</child>
		<child test="hai">omg</child>
	</parent>
	<copy>
		<default/>
	</copy>
</root>
<?php

$original = ob_get_contents();

ob_end_clean();

$Xml = new DomQuery;

$Nodes = $Xml->load($original)
	->path('//*[@test]')
	->setAttr('tests', 'blah');

foreach($Nodes as $Node)
{
echo $Node->nodeValue;
}


header('Content-Type: text/xml');

die($Xml->saveXml());
