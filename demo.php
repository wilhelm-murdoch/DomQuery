<?php

require_once 'domquery.php';

ob_start();

?>
<root>
	<books total="3">
		<book id="1">
			<title>The God Delusion</title>
			<author>Richard Dawkins</author>
		</book>
		<book id="2">
			<title>Brave New World</title>
			<author>Aldous Huxley</author>
		</book>
		<book id="3">
			<title>1984</title>
			<author>George Orwell</author>
		</book>
	</books>
</root>
<?php

$original = ob_get_contents();

ob_end_clean();

try
{
	$DomQuery = new DomQuery;

	print_r($DomQuery->load($original, '//book[@id = 2]')->prepend($DomQuery->createElement('omg', print_r($DomQuery, true)))->save(SAVE_MODE_S, $DomQuery));
}
catch(DOMException $Exception)
{
	echo $Exception->getMessage();
}