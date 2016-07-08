<?php
  include('./common.php');

  # Send headers
  header('Date: '.gmdate('D, d M Y H:i:s \G\M\T', time()));
  header('Last-Modified: '.gmdate('D, d M Y H:i:s \G\M\T', time()));
  header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + $refresh));
  header("Cache-Control: no-cache, must-revalidate");
  header("Pragma: no-cache");

  print "<HTML>\n<!-- RTG2 Version $VERSION -->\n<HEAD>\n";

  /* Connect to RTG MySQL Database */
  $dbc=@new mysqli($host, $user, $pass, $db) or
  $dbc=@new mysqli("$host:/var/lib/mysql/mysql.sock", $user, $pass, $db) or 
     die ("MySQL Connection Failed, Check Configuration.");

  # Global variables off by default in newer versions of PHP
  if (!$PHP_SELF) {
    $PHP_SELF = "view.php";
    $rid = $_GET['rid'];
    $iid = $_GET['iid'];
  }

  # Determine router, interface names as necessary
  if ($rid && $iid) {
    $selectQuery="SELECT a.name, a.description, a.speed, b.name AS router FROM interface a, router b WHERE a.rid=b.rid AND a.rid=$rid AND a.id=$iid";
    $selectResult=$dbc->query($selectQuery);
    $selectRow=$selectResult->fetch_object();
    $interfaces = $selectResult->num_rows;
    $name = $selectRow->name;
    $description = $selectRow->description;
    $speed = ($selectRow->speed)/1000000;
    $router = $selectRow->router;
  } else if ($rid && !$iid) {
    $selectQuery="SELECT name AS router from router where rid=$rid";
    $selectResult=$dbc->query($selectQuery);
    $selectRow=$selectResult->fetch_object();
    $router = $selectRow->router;
  }

  # Generate Title 
  echo "<TITLE>RTG2: ";
  if ($rid && $iid) echo "$router: $name";
  else if ($rid && !$iid) echo "$router";
  echo "</TITLE>\n";

  print "<meta HTTP-EQUIV=\"Refresh\" CONTENT=\"300\">\n";
  print "<meta HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n";
  print "<meta HTTP-EQUIV=\"Cache-Control\" CONTENT=\"no-cache\">\n";
  print "<meta HTTP-EQUIV=\"Expires\" CONTENT=\"".gmdate('D, d M Y H:i:s \G\M\T', time() + 300)."\">\n";
  print "<meta HTTP-EQUIV=\"Generator\" CONTENT=\"RTG $VERSION\">\n";
  print "<meta HTTP-EQUIV=\"Date\" CONTENT=\"".gmdate('D, d M Y H:i:s \G\M\T', time())."\">\n";
  print "<meta HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=iso-8859-1\">\n";
?>

</HEAD>
<BODY BGCOLOR="ffffff">
<A HREF="http://github.com/Prophidys/RTG2" ALT="[RTG2 Home Page]">
<IMG SRC="rtg.png" BORDER="0"></A><P><HR>

<?php
 echo "<FORM ACTION=\"$PHP_SELF\" METHOD=\"GET\">\n";

 $et = time();
 
 if (!$rid) {
    print "Monitored Devices: <P>\n";
    $selectQuery="SELECT rid, name FROM router";
    $selectResult=$dbc->query($selectQuery);
    if ($selectResult->num_rows <= 0) 
      print "<BR>No Routers Found.<BR>\n";
    else {
      print "<UL>\n";
      while ($selectRow=$selectResult->fetch_object()){
        print "<LI><A HREF=\"$PHP_SELF?rid=$selectRow->rid\">";
        print "$selectRow->name</A><BR>\n";
      }
      print "</UL>\n";
    }
 }

 if ($rid && $iid) {
    if ($interfaces <= 0)
      print "<BR>Interface $iid Not Found for Router $router (ID: $rid).<BR>\n";
    else {
      print "<TABLE>\n";
      print "<TD>System:<TD>$router\n";
      print "<TR><TD>Interface:<TD>$name\n";
      print "<TR><TD>Description:<TD>$description\n";
      print "<TR><TD>Speed:<TD>$speed Mbps\n";
      print "<TR><TD>Page Generated:<TD>".gmdate('D, d M Y H:i:s \G\M\T', time())."\n";
      print "</TABLE><HR>\n";

      $bt = $et - (60*60*24);
      print "<B>Day View:</B><BR>\n";
      print "<IMG SRC=\"rtgplot.cgi?DO=1:ifInOctets_".$rid.":".$iid."&DO=2:ifOutOctets_".$rid.":".$iid."&LO=1:1:factor=8:In&LO=2:2:factor=8:Out&PO=".$router."-".$name.":500:150::".$bt.":".$et."\">\n";
      print "<BR><B>$router: $name ($description)</B>\n";
      print "<BR><HR>\n";

      $bt = $et - (60*60*24*7);
      print "<B>Week View:</B><BR>\n";
      print "<IMG SRC=\"rtgplot.cgi?DO=1:ifInOctets_".$rid.":".$iid."&DO=2:ifOutOctets_".$rid.":".$iid."&LO=1:1:factor=8:In&LO=2:2:factor=8:Out&PO=".$router."-".$name.":500:150::".$bt.":".$et."\">\n";
      print "<BR><B>$router: $name ($description)</B>\n";
      print "<BR><HR>\n";

      $bt = $et - (60*60*24*30);
      print "<B>Month View:</B><BR>\n";
      print "<IMG SRC=\"rtgplot.cgi?DO=1:ifInOctets_".$rid.":".$iid."&DO=2:ifOutOctets_".$rid.":".$iid."&LO=1:1:factor=8:In&LO=2:2:factor=8:Out&PO=".$router."-".$name.":500:150::".$bt.":".$et."\">\n";
      print "<BR><B>$router: $name ($description)</B>\n";
      print "<BR><BR>\n";
    }
    print "<INPUT TYPE=\"SUBMIT\" VALUE=\"Back to Main\">\n";
 }

 if ($rid && !$iid) {
    $selectQuery="SELECT id, name, description FROM interface WHERE rid=$rid";
    $selectResult=$dbc->query($selectQuery);
    $interfaces = $selectResult->num_rows;
    if ($interfaces <= 0) 
      print "<BR>No Interfaces Found for Router $router (ID: $rid).<BR>\n";
    else {
      $bt = $et - (60*60*12);
      print "<TABLE>\n";
      print "<TD>System:<TD>$router\n";
      print "<TR><TD>Interfaces:<TD>$interfaces\n";
      print "<TR><TD>Page Generated:<TD>";
      print gmdate('D, d M Y H:i:s \G\M\T', time())."\n";
      print "</TABLE><HR>\n";
      print "<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"10\">\n";
      while ($selectRow=$selectResult->fetch_object()){
        $ids[$selectRow->id] = $selectRow->name; 
	$desc[$selectRow->id] = $selectRow->description;
	$iid = $selectRow->id;
	print "<TD><A HREF=\"$PHP_SELF?rid=$rid&iid=$iid\">\n";
        print "<IMG SRC=\"rtgplot.cgi?DO=1:ifInOctets_".$rid.":".$iid."&DO=2:ifOutOctets_".$rid.":".$iid."&LO=1:1:factor=8:In&LO=2:2:factor=8:Out&PO=".$router."-".$name.":500:150::".$bt.":".$et."\">\n";
	print "</A><BR>\n";
        print "<B>$selectRow->name ($selectRow->description)</B>\n";
        if ($even) {
	  $even = 0;
	  print "<TR>\n";
	}
	else $even = 1;
      }
      print "</TABLE>\n";
    }
    print "<INPUT TYPE=\"SUBMIT\" VALUE=\"Back to Main\">\n";
  }

  if ($dbc) $dbc->close();
  echo "</FORM>\n";
?>

<HR>
<FONT FACE="Arial" SIZE="2">
<?php
 print "<A HREF=\"http://github.com/Prophidys/RTG2\">RTG2</A> Version $VERSION</FONT>";
?>
</BODY>
</HTML>
