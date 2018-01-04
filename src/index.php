<?php
    //$mailto="hubert@loehers.de;gerda@loehers.de;karsten@loehers.de;stef@loehers.de";
    //$serverurl="http://orga/zeit/";
    //$hinweis="<font color=\"red\"><b>Die Zeiterfassung ist heute ab 16:30 wegen Kabelarbeiten auf der Garage<br> nicht verfuegbar.Ebenso alle anderen Programme. Weitersagen!</b></font>";
    if(0 && $REMOTE_ADDR != '192.168.1.50') die("<h2>Löhers Zeiterfassung</h2><h3>Anfrage kann nicht bearbeitet werden, da gerade Wartungsarbeiten vorgenommen werden.</h3>");

    session_start();
    session_register('mid','passwort');
    if($hauptauswahl) $mid=0;
    if($newmid) $mid=$newmid;

    function query($query) {
	mysql_query("SET sql_mode = '';");
        if(!$result=mysql_query($query)) {
            echo "<pre><b>mysql-fehler\nquery: $query\nErrortext: " . mysql_error() . "\nBitte sofort an Stefan wenden</b></pre>";
            exit;
        }
        return $result;
    }
    function foot() {
        echo '</td></tr></table>';
        echo "</body></html>";
        exit;
    }

if(!@mysql_connect("mysql","root","zeiterfassung")) {
	echo '<font face="sans">L&ouml;hers Zeiterfassung gerade nicht nutzbar, da vermutlich ein Backup l&auml;uft. Sollte das Problem l&auml;nger bestehen, Stefan anrufen - Stichwort "mysql down"</font>';
	exit;
}
    mysql_select_db("zeiterfassung");

if($tododone) {
	query("UPDATE todo SET done=NOW() WHERE id='$tododone'");
	header('Location: ' . $PHP_SELF);
}
if($todonotdone) {
	query("UPDATE todo SET done='0000-00-00 00:00:00' WHERE id='$todonotdone'");
	header('Location: ' . $PHP_SELF);
}
if(trim($todoadd)) {
	$result=query("INSERT todo SET date=NOW(), text='" . htmlentities($todoadd) . "'");
	header('Location: ' . $PHP_SELF);
}

?>
<html>
<head>
<title>Loehers Zeiterfassung</title>
<style type="text/css">
<!--
    body {margin-top:0px;margin-left:0px;margin-right:0px}
    body,table,td,input,select,textarea,a {font-family:Verdana,Arial,Sans;font-size:12pt;}
    a:link,a:visited {color:#0000ff}
    a:active,a:hover {color:#ff0000}
    a:link.navi,a:visited.navi {color:#ffffff}
    a:active.navi,a:hover.navi {color:#ff0000}
//-->
</style>
</head>
<body>
<table width="100%" height="60" border="0" cellpadding="0" cellspacing="0"><tr><td bgcolor="#bbbbbb" colspan="2"><img src="zeiterfassung.png"></td></tr>
<tr><td><?php echo $hinweis; ?></td></tr>
</table>


<?php
    $heute=date("Y-m-d");

    if($ekunde && $ekundennr) {
    	query("INSERT INTO kunden SET knr='$ekundennr',kunde='$ekunde'");
    	$selectkunde=1;
    	$showallkunden=1;
    }
    if($delkunde) {
    	list($denied) = mysql_fetch_row(query("SELECT COUNT(*) FROM objekte WHERE kunde='$delkunde'"));
    	if(!$denied) query("DELETE FROM kunden WHERE id='$delkunde'");
    	$delkunden=1;
	}
    if($objektaktion)
    {
        if($objektaktion == 'insert')
        {
            if(!$eobjekt) die("Kein Objektname");
            query("INSERT INTO objekte SET objekt='$eobjekt',kunde='$kunde',abgeschlossen='n',notiz='',start=NOW()");
            $showobjekt=mysql_insert_id();
        }
        if($objektaktion == 'abschliessen') {
            query("UPDATE objekte SET abgeschlossen='j',ende=NOW() WHERE id='$updateid'");
/*	    
	    $result=query("SELECT o.objekt,k.kunde,k.knr FROM objekte o,kunden k WHERE o.kunde=k.id AND o.id='$updateid'");
    	    list($o_objekt,$o_kunde,$o_kundennr) = mysql_fetch_row($result);
            $mailmsg="Für den Kunden $o_kunde ($o_kundennr) wurde das Objekt $o_objekt abgeschlossen. " . $serverurl . "?disob=$updateid";
            mail($mailto,"[Zeit] Objekt $o_objekt ($o_kunde) abgeschlossen", $mailmsg ,"From: stef@loehers.de");
*/
	}
        if($objektaktion == 'oeffnen')
            query("UPDATE objekte SET abgeschlossen='n' WHERE id='$updateid'");
        /* if($objektaktion == 'delete')
        {
            query("DELETE FROM objekte WHERE id='$updateid'");
            query("DELETE FROM jobs WHERE objekt='$updateid'");
            query("DELETE FROM material WHERE objekt='$updateid'");
        } */
    }

    if($showalljobs) {
        echo "<a href=\"?showalljobs=0\">zurück</a>";     

        $result=query("SELECT id,mitarbeiter FROM mitarbeiter WHERE aktiv='j' ORDER BY mitarbeiter");
        echo "<form action=\"$PHP_SELF\">";
        echo "<input type=\"hidden\" name=\"showalljobs\" value=\"1\">";
        echo "<select name=\"ebmid\">\n";
        echo "<option value=\"0\">alle\n";
        while(list($t_id,$t_mitarbeiter) = mysql_fetch_row($result))
        {
            if($ebmid == $t_id)
                echo "<option value=\"$t_id\" selected>$t_mitarbeiter\n";
            else
                echo "<option value=\"$t_id\">$t_mitarbeiter\n";
        }
        echo "</select>";
        if($ebvon && $ebnach)
        {
            $altest=$ebvon;
            $neuest=$ebnach;
        } else {
            $result=query("SELECT UNIX_TIMESTAMP(datum) FROM jobs ORDER BY datum DESC LIMIT 1");
            list($neuest_timestamp) = mysql_fetch_row($result);
            $altest=date("Y-m-01",$neuest_timestamp);
            $neuest=date("Y-m-d",$neuest_timestamp);
        }
        $ebvon=$altest;
        $ebnach=$neuest;
        echo " <b>von</b> <input name=\"ebvon\" value=\"$altest\" size=\"10\" maxlength=\"10\"> <b>bis</b> <input name=\"ebnach\" value=\"$neuest\" size=\"10\" maxlength=\"10\"> ";
        echo " <input type=\"submit\" value=\"beschränken\">";
	echo "</form>";
	echo " <a href=\"?showalljobs=1&ebmid=$ebmid&ebvon=" . date("Y-m-01", strtotime("-1 month", strtotime($ebnach))) . "&ebnach=" . date("Y-m-00", strtotime($ebnach)) . "\">Monatsansicht</a>:";
	echo " <a href=\"?showalljobs=1&ebmid=$ebmid&ebvon=" . date("Y-m-d", strtotime("-1 month", strtotime($ebvon))) . "&ebnach=" . date("Y-m-00", strtotime($ebnach)) . "\">Monat vor</a>";
	echo " <a href=\"?showalljobs=1&ebmid=$ebmid&ebvon=" . date("Y-m-d", strtotime("+1 month", strtotime($ebvon))) . "&ebnach=" . date("Y-m-00", strtotime("+2 month", strtotime($ebnach))) . "\">Monat weiter</a>";
	echo " &nbsp; <a href=\"?showalljobs=1&ebmid=$ebmid&ebvon=" . $ebnach . "&ebnach=" . $ebnach . "\">Tagesansicht</a>:";	
	echo " <a href=\"?showalljobs=1&ebmid=$ebmid&ebvon=" . date("Y-m-d", strtotime("-1 day", strtotime($ebvon))) . "&ebnach=" . date("Y-m-d", strtotime("-1 day", strtotime($ebnach))) . "\">Tag vor</a>";
	echo " <a href=\"?showalljobs=1&ebmid=$ebmid&ebvon=" . date("Y-m-d", strtotime("+1 day", strtotime($ebvon))) . "&ebnach=" . date("Y-m-d", strtotime("+1 day", strtotime($ebnach))) . "\">Tag weiter</a>";
	echo " <a href=\"?showalljobs=1\">Beschränkung aufheben</a>";
	//echo " <a href=\"#\">Tag zur&uuml;ck</a></form>";
	echo "<br><br>";
	echo "<table border>";
	echo "<tr><td>Mo</td><td>Di</td><td>Mi</td><td>Do</td><td>Fr</td><td>Sa</td><td>So</td></tr>";
	$woche=(date("w",strtotime(date("Y-m-01",strtotime($ebvon))))+1) % 7;
	$monthnyear=(date("Y-m",strtotime($ebvon)));
	for($a=1;$a<($woche-1);$a++) // Suche 1.
	{
		echo "<td>&nbsp;</td>";
	}
	for($a=1;$a<32;$a++) {
		$query="SELECT SUM(zeit) FROM jobs WHERE datum='$monthnyear-$a'";
		if($ebmid) $query=$query . " AND mitarbeiter='$ebmid'";
		list($zeitAmTag)=mysql_fetch_row(query($query));
		echo "<td><a href=\"?showalljobs=1&ebmid=$ebmid&ebvon=$monthnyear-$a&ebnach=$monthnyear-$a\">$a</a>: $zeitAmTag" . "h";
		echo "</td>";
		if(($a+$woche-2) % 7 == 0) echo "</tr><tr>";
		
	}
	echo "</tr>";
	echo "<table>";
	echo "<br><br>";
        $callvari="?showalljobs=1&ebmid=$ebmid&ebvon=$ebvon&ebnach=$ebnach";
        if(!$order) $order=5;
        else $order=$order+1;
        $spalte[1]="Mitarbeiter";
        $spalte[2]="Kunde";
        $spalte[3]="Objekt";
        $spalte[4]="Datum";
        $spalte[5]="Zeit";
        $spalte[6]="Arbeit";
        $felder[2]="m.mitarbeiter";
        $felder[3]="k.kunde";
        $felder[4]="o.objekt";
        $felder[5]="j.datum DESC";
        $felder[6]="j.zeit";
        $felder[7]="j.arbeit";
        if($ebmid) $besmidwhe=" AND m.id='$ebmid'";
        if($ebvon) $result=query("SELECT j.id,m.mitarbeiter,k.kunde,o.objekt,DATE_FORMAT(j.datum,'%d.%m.%y'),j.zeit,j.arbeit FROM jobs j,mitarbeiter m,objekte o,kunden k WHERE j.mitarbeiter=m.id AND j.objekt=o.id AND o.kunde=k.id$besmidwhe AND j.datum >= '$ebvon' AND j.datum <= '$ebnach' ORDER BY $felder[$order],datum DESC");
        else $result=query("SELECT j.id,m.mitarbeiter,k.kunde,o.objekt,DATE_FORMAT(j.datum,'%d.%m.%y'),j.zeit,j.arbeit FROM jobs j,mitarbeiter m,objekte o,kunden k WHERE j.mitarbeiter=m.id AND j.objekt=o.id AND o.kunde=k.id$besmidwhe ORDER BY $felder[$order],datum DESC");
        echo '<table bordercolor="#000000" border="1">';
        echo "\n<tr>";
        reset($spalte);
        while(list($myfield,$mydesc) = each($spalte))
            echo "<th><a href=\"$callvari&order=$myfield\">$mydesc</a></th>";
        echo "</tr>\n";
        while($row=mysql_fetch_row($result))
        {
            echo "<tr>";
            reset($spalte);
            while(list($myfield,$mydesc) = each($spalte))
            {
                if(is_numeric($row[$myfield])) $$mydesc+=$row[$myfield];
                echo "<td>$row[$myfield]</td>";
            }
            echo "</tr>\n";
        }
        echo "<tr>";
        reset($spalte);
        for($a=0;list($myfield,$mydesc) = each($spalte);$a++)
        {
            if(isset($$mydesc)) $feldsumme=$$mydesc;
            else $feldsumme="";
            echo "<td>$feldsumme</td>";
        }
        echo "</table><br><br>";
        $result=query("SELECT id,mitarbeiter FROM mitarbeiter ORDER BY mitarbeiter");
        echo "<form action=\"$PHP_SELF\">";
        echo "<input type=\"hidden\" name=\"showalljobs\" value=\"1\">";
        echo "<select name=\"ebmid\">\n";
        echo "<option value=\"0\">alle\n";
        while(list($t_id,$t_mitarbeiter) = mysql_fetch_row($result))
        {
            if($ebmid == $t_id)
                echo "<option value=\"$t_id\" selected>$t_mitarbeiter\n";
            else
                echo "<option value=\"$t_id\">$t_mitarbeiter\n";
        }
        echo "</select>";
        echo " <b>von</b> <input name=\"ebvon\" value=\"$altest\" size=\"10\" maxlength=\"10\"> <b>bis</b> <input name=\"ebnach\" value=\"$neuest\" size=\"10\" maxlength=\"10\"> ";
        echo " <input type=\"submit\" value=\"beschränken\"></form>";
        foot();
    }
    if($showallobjekts) {

        if(!isset($showlfob)) $showlfob=1;
        echo "<a href=\"?showallobjekts=0\">zurück</a> ";
        if($showlfob) echo "<a href=\"?showallobjekts=1&showlfob=0\">Abgeschlossene Objekte zeigen</a>";
        else echo "<a href=\"?showallobjekts=1&showlfob=1\">Laufende Objekte zeigen</a>";
        $callvari="?showallobjekts=1&showlfob=$showlfob";
        if(!$order) {
            if(!$showlfob) $order="5 DESC";
            else $order=2;
        } else
            $order=$order+1;
        if($showlfob) echo "<h3>Laufende Objekte</h3>";
        else echo "<h3>Abgeschlossene Objekte</h3>";
        $spalte[1]="Kunde";
        $spalte[2]="Objekt";
        $spalte[3]="Start-Datum";
        if(!$showlfob) {
            $spalte[4]="Ende-Datum";
            $tmp_abstr='j';
        }
        else $tmp_abstr='n';
        $spalte[5]="Stunden";
        $showlimit=75;
        if(!$showoverlimit) $result=query("SELECT o.id,k.kunde,o.objekt,start,ende,sum(j.zeit) FROM kunden k,objekte o LEFT JOIN jobs j ON j.objekt=o.id WHERE o.kunde=k.id AND o.abgeschlossen = '$tmp_abstr' GROUP BY o.id ORDER BY $order,2 LIMIT $showlimit");
        else  $result=query("SELECT o.id,k.kunde,o.objekt,start,ende,sum(j.zeit) FROM kunden k,objekte o LEFT JOIN jobs j ON j.objekt=o.id WHERE o.kunde=k.id AND o.abgeschlossen = '$tmp_abstr' GROUP BY o.id ORDER BY $order,2");
        echo '<table width="800" bordercolor="#000000" border="1">';
        echo "<tr>";
        reset($spalte);
        while(list($myfield,$mydesc) = each($spalte))
            echo "<th><a href=\"$callvari&order=$myfield\">$mydesc</a></th>";
        echo "</tr>";
        while($row=mysql_fetch_row($result))
        {
            echo "<tr>";
            reset($spalte);
            while(list($myfield,$mydesc) = each($spalte))
                echo "<td><a href=\"?disob=$row[0]\">$row[$myfield]</a></td>";
            echo "</tr>";
        }
        $t_anzahlob=mysql_num_rows($result);
        echo "</table><br><br>Es werden $t_anzahlob Objekte angezeigt ";
        if($t_anzahlob == $showlimit) echo "<a href=\"?showallobjekts=1&showlfob=$showlfob&showoverlimit=1\">alle anzeigen</a><br>";
        if($showlfob) echo "<a href=\"?showallobjekts=1&showlfob=0\">Abgeschlossene Objekte zeigen</a>";
        else echo "<br><br><a href=\"?showallobjekts=1&showlfob=1\">Laufende Objekte zeigen</a>";
        foot();
    }
    if($disob) {
        $result=query("SELECT o.objekt,o.notiz,k.kunde,k.knr,o.abgeschlossen,o.start,o.ende FROM objekte o, kunden k WHERE o.kunde=k.id AND o.id='$disob'");
        list($t_objekt,$t_notiz,$t_kunde,$t_knr,$t_abgeschlossen,$t_start,$t_ende) = mysql_fetch_row($result);
        echo "<b>Kunde:</b> $t_kunde - $t_knr<br>\n";
        echo "<b>Objekt:</b> $t_objekt<br>\n";
	echo "<b>Notizen:</b> " . nl2br(htmlentities($t_notiz)) . "<br>";
        echo "<b>Start:</b> $t_start<br>\n";
        if($t_abgeschlossen=='j') echo "<b>Ende:</b> $t_ende <a href=\"?disob=$disob&objektaktion=oeffnen&updateid=$disob\">Objekt wieder öffnen</a>\n";
        else echo "Dieses Objekt ist noch nicht abgeschlossen und läuft immer noch <a href=\"?disob=$disob&objektaktion=abschliessen&updateid=$disob\">abschliessen</a>\n";
	 echo '<a href="javascript:window.print()">drucken</a>';
        echo "<br><br><br>";

            if($t_abgeschlossen=='n') echo "<h4>bisheriger Zeitaufwand</h4>";
            else echo "<h4>Zeitaufwand</h4>";
            $result=query("SELECT m.id,m.mitarbeiter,j.datum,j.zeit,j.arbeit FROM mitarbeiter m,jobs j WHERE j.mitarbeiter=m.id AND j.objekt='$disob' ORDER BY j.datum");
            echo "<table bordercolor=\"#000000\" border=\"1\" width=\"100%\">";
            echo "<tr><th>Mitarbeiter</th><th>Datum</th><th>Arbeit</th><th>Zeit</th></tr>";
            while(list($t_mid,$t_mitarbeiter,$t_datum,$t_zeit,$t_arbeit) = mysql_fetch_row($result))
            {
                $gesamt_zeit+=$t_zeit;
                $t_dzeit=strtr($t_zeit,'.',',');
                $t_ddatum=date("d.m.y",strtotime($t_datum));
                echo "<tr><td>$t_mitarbeiter</td><td>$t_ddatum</td><td>$t_arbeit</td><td nowrap>$t_dzeit h</td></tr>\n";
            }
            $gesamt_dzeit=strtr($gesamt_zeit,'.',',');
            echo "<tr><td colspan=\"3\"><b>Gesamt</b></td><td nowrap><b>$gesamt_dzeit h</b></td></tr>";
            echo "</table>";

            if($t_abgeschlossen=='n') echo "<h4>bisheriges Material</h4>";
            else echo "<h4>Material</h4>";
            $result=query("SELECT a.anr,a.artikel,sum(m.menge) FROM material m,artikel a WHERE m.artikel=a.id AND m.objekt='$disob' GROUP BY a.artikel ORDER BY a.anr");
            echo "<table bordercolor=\"#000000\" border=\"1\" width=\"100%\">";
            echo "<tr><th>Menge</th><th>A-Nr</th><th>A-Bezeichnung</th></tr>";
            while(list($q_anr,$q_bezeichnung,$q_menge)=mysql_fetch_row($result))
            {
                $q_menge=strtr($q_menge,'.',',');
                echo "<tr><td>$q_menge</td><td>$q_anr</td><td>$q_bezeichnung</td></tr>";
            }
            echo "</table>";

        echo "<br><br>";
        if($t_abgeschlossen=='n') $showlfob=1;
        echo "<a href=\"?showallobjekts=1&showlfob=$showlfob\">Zur&uuml;ck zur &Uuml;bersicht</a>";

        foot();
    }

    if(!$mid) {
        echo '<center>';
	echo '<table height="100%" border="0" cellspacing="10"><tr><td width="50%" valign="top">';
	echo '<font size="4"><b>Zeit- und Material erfassen f&uuml;r...</b></font><br><br>';

        $result=query("SELECT id,mitarbeiter,kommentar FROM mitarbeiter WHERE aktiv='j' ORDER BY mitarbeiter");
        while(list($id,$mitarbeiter,$kommentar) = mysql_fetch_row($result))
            echo "<font size=\"4\"><a href=\"?newmid=$id\">$mitarbeiter</a></font><br><br>";
	echo '</td><td width="40">&nbsp;</td><td align="left" valign="top">';

		echo '<font size="4"><b>Noch zu erledigende Arbeiten</b></font><br><br>';

		$result=query("SELECT id,date,done,text FROM todo WHERE DATE_SUB( NOW(), INTERVAL 1 DAY) <= done OR done = '0000-00-00 00:00:00' ORDER BY date");
		while(list($id,$date,$done,$text) = mysql_fetch_row($result))
		{
			if($done != '0000-00-00 00:00:00') echo "<s>$text</s> <a href=\"?todonotdone=$id\">doch nicht</a><br>";
			else echo "$text <a href=\"?tododone=$id\">fertig</a><br>";
		}
		echo "<br><form action=\"$PHP_SELF\" method=\"GET\">";
		echo '<input type="text" name="todoadd" size="20">
		<input type="submit" value="hinzu"></form>';
        echo '<br><font size="4"><b>&Uuml;bersichten</b></font><br><br>';
	echo "<a href=\"?showallobjekts=1\">alle Objekte</a>";
        echo "<br><br><a href=\"?showalljobs=1\">alle Arbeiten</a><br><br>";

        foot();
    }
    $result=query("SELECT mitarbeiter FROM mitarbeiter WHERE id='$mid'");
    list($mitarbeiter) = mysql_fetch_row($result);
    echo "Mitarbeiter: $mitarbeiter <a href=\"?hauptauswahl=1\">wechseln</a> ";
    if(($showobjekt || $newobjekt) && !$newartikel) echo "<a href=\"?selectkunde=1\">Zurück zur Kunden- / Objektauswahl</a>";
    if($newartikel && $showobjekt) echo "<a href=\"?showobjekt=$showobjekt\">abbrechen und zurück zum Objekt</a>";
    if($selectkunde) echo "<a href=\"?selectkunde=0\">Zurück zur Stundenübersicht</a>";
    if(!($showobjekt || $selectkunde || $newobjekt)) echo "<a href=\"$PHP_SELF?selectkunde=1\">Arbeit/Material hinzufügen</a>";
    echo '<table height="100%" width="100%"><tr><td align="center">';
    if($selectkunde)
    {
        echo "<h3>Kunde / Objektauswahl</h3>";
        echo "<a href=\"?selectkunde=0\">zurück</a>";
        echo "<table><tr><td>";
        if($showallkunden) {
        	$result=query("SELECT k.id, k.kunde, COUNT(o.id) FROM kunden k LEFT OUTER JOIN objekte o ON k.id=o.kunde AND o.abgeschlossen='n' GROUP BY k.id ORDER BY k.kunde");
	        while(list($k_id,$k_kunde,$anzahl_objekte) = mysql_fetch_row($result))
	        {
	            if($anzahl_objekte)
	            {
	                echo "\n$k_kunde<br>\n";
	                $result2=query("SELECT id,objekt,DATE_FORMAT(start,'%d.%m.%y') FROM objekte WHERE kunde='$k_id' AND abgeschlossen='n'");
	                while(list($o_id,$o_objekt,$o_start)=mysql_fetch_row($result2))
	                {
	                    echo "\n&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"?showobjekt=$o_id#linie\">$o_objekt</a> (gestartet am $o_start)<br>\n";
	                }
	                echo "&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"?newobjekt=1&kunde=$k_id\">neues Objekt</a><br>";            
	            }
	            else
	            {
	               echo "<a href=\"?newobjekt=1&kunde=$k_id\">$k_kunde</a><br>";            
	            }
	        }
	        echo "<br><a href=\"?neukunde=1\">Neuen Kunden hinzufügen</a> <a href=\"?delkunden=1\">Kunden löschen</a>";
	    } else {
	    	echo "<h4>Aktive Objekte</h4>";
	    	$result=query("SELECT o.id,o.objekt,k.kunde,DATE_FORMAT(o.start,'%d.%m.%y') FROM objekte o,kunden k WHERE o.abgeschlossen='n' AND o.kunde=k.id ORDER BY k.kunde");
	    	echo "<table>";
	    	while(list($o_id,$o_objekt,$o_kunde,$o_start)=mysql_fetch_row($result))
	    	{
	    		echo "<tr><td>$o_kunde</td><td><a href=\"?showobjekt=$o_id#linie\">$o_objekt</a></td><td>gestartet am $o_start</td></tr>\n";
	    	}
	    	echo "</table>";
	    	echo "<br><a href=\"?selectkunde=1&showallkunden=1\">Alles zeigen / Neues Objekt anlegen</a>";
	    }

        echo "</td></tr></table>";
        
        foot();
    }
    if($neukunde) {
        echo "<h4>Neuen Kunden anlegen</h4>";
        echo "<form action=\"$PHP_SELF\" method=\"POST\" name=\"kundenform\" onSubmit=\"return chkKForm()\">";
        echo "Kundennummer <input name=\"ekundennr\" tabindex=\"1\"><br>";
        echo "Kunde <input name=\"ekunde\" tabindex=\"2\"><br><br>";
        echo "<a href=\"?selectkunde=1&showallkunden=1\">Abbrechen</a> <input type=\"submit\" value=\"Anlegen\">";
        echo "<script type=\"text/javascript\">";
        echo '<!--
            document.kundenform.ekundennr.focus();

            function chkKForm() {
                if(document.kundenform.ekundennr.value == "") {
                    alert("Bitte Kundennummer erfragen");
                    document.kundenform.ekundennr.focus();
                    return false;
                }
                if(document.kundenform.ekunde.value == "") {
                    alert("Bitte Kundennamen eingeben");
                    document.kundenform.ekunde.focus();
                    return false;
                }
            }

            //-->
            </script>';
        foot();    	
    }
    if($delkunden) {
    	echo "<h3>Kunden löschen</h3>";
    	echo "<a href=\"?selectkunde=1&showallkunden=1\">zurück</a>";
    	echo "<br>Hinweis: Es können nur Kunden gelöscht werden wo noch nie ein Objekt erstellt wurde. Diese Liste steht zur Auswahl:<br><br>";
    	$result=query("SELECT k.id,k.knr,k.kunde,COUNT(o.id) FROM kunden k LEFT OUTER JOIN objekte o ON k.id=o.kunde GROUP BY k.id ORDER BY k.kunde");
    	echo "<table>";
    	while(list($k_id,$k_knr,$k_kunde,$k_coid) = mysql_fetch_row($result))
    	{
    		if($k_coid==0) echo "<tr><td>$k_kunde</td><td>$k_knr</td><td><a href=\"?delkunde=$k_id\">löschen</a></td></tr>";
    	}
    	echo "</table>";
    	foot();
    }
    if($newobjekt && $kunde)
    {

        list($kundenname,$kundennr) = mysql_fetch_row(query("SELECT knr,kunde FROM kunden WHERE id='$kunde'"));
        echo "<h4>Neues Objekt für Kunde $kundenname ($kundennr)</h4>";
        echo "<form action=\"$PHP_SELF\" method=\"POST\" name=\"objektform\" onSubmit=\"return chkOForm()\">";
        echo "<input type=\"hidden\" name=\"objektaktion\" value=\"insert\">";
        echo "<input type=\"hidden\" name=\"kunde\" value=\"$kunde\">";
        echo "Objektname <input name=\"eobjekt\" tabindex=\"1\"><br><br>";
        echo "<a href=\"?selectkunde=1&showallkunden=1\">Abbrechen</a> <input type=\"submit\" value=\"Anlegen\">";
        echo "<script type=\"text/javascript\">";
        echo '<!--
                    document.objektform.eobjekt.focus();

            function chkOForm() {
                if(document.objektform.eobjekt.value == "") {
                    alert("Bitte angeben um was für ein Objekt es sich handelt");
                    document.objektform.eobjekt.focus();
                    return false;
                }
            }

            //-->
            </script>';
        foot();
    }    
    if($artikelaktion)
        if($artikelaktion == 'insert')
            query("INSERT INTO artikel SET anr='$eanr', artikel='$eartikel'");
    if($newartikel) {
        echo "<script type=\"text/javascript\">";
        echo '<!--
            function chkFormular() {
                if(document.newartikel.eanr.value == "" || document.newartikel.eartikel.value == "")
                {
                    alert("Felder wurden nicht richtig ausgefüllt");
                    return false;
                }
            }
            //-->
            </script>';
        echo "<h4>Neuen Artikel anlegen</h4>";
        echo "<form name=\"newartikel\" action=\"$PHP_SELF\" method=\"POST\" onSubmit=\"return chkFormular()\">";
        echo "<input type=\"hidden\" name=\"artikelaktion\" value=\"insert\">";
        echo "<input type=\"hidden\" name=\"showobjekt\" value=\"$showobjekt\">";
        echo "Artikelnummer: <input name=\"eanr\"><br>";
        echo "Artikelbezeichnung: <input name=\"eartikel\"><br><br>";
        echo "<a href=\"?showobjekt=$showobjekt\">Abbrechen</a> <input type=\"submit\" value=\"Anlegen\">";
        foot();
    }
    if(isset($objektnotiz))
	$result=query("UPDATE objekte SET notiz='$objektnotiz' WHERE id='$showobjekt';");

    if($objektnotizedit) {
	$result=query("SELECT o.objekt,o.notiz,k.kunde,k.knr,o.abgeschlossen FROM objekte o,kunden k WHERE o.kunde=k.id AND o.id='$showobjekt'");
    	list($o_objekt,$o_notiz,$o_kunde,$o_kundennr,$abgeschlossen) = mysql_fetch_row($result);
        echo "<b>Kunde:</b> $o_kunde ($o_kundennr)<br> <b>Objekt:</b> $o_objekt";
        echo "<br>\n";
	echo "<form action=\"$PHP_SELF#linie\" method=\"POST\">";
	echo "<b>Notizen:</b><br><textarea name=\"objektnotiz\" cols=\"50\" rows=\"10\">" . htmlentities($o_notiz) . "</textarea><br>";
	echo "<input type=\"hidden\" name=\"showobjekt\" value=\"$showobjekt\">";
	echo "<input type=\"submit\" value=\"Okay\">";
	echo "</form>";
	foot();
    }
	        
    if($showobjekt) {
        if($jobaktion) {
            if($jobaktion == 'insert')
            {
                $ezeit=strtr($ezeit,',','.');
                $eddatum=date("Y-m-d",mktime(0,0,0,substr($edatum,3,2),substr($edatum,0,2),substr($edatum,6,4)));
                $earbeit=trim(strtr($earbeit, "\r\n", "  "));
                query("INSERT INTO jobs (mitarbeiter,objekt,datum,zeit,arbeit) VALUES ('$mid','$showobjekt','$eddatum','$ezeit','$earbeit')");
            }
            if($jobaktion == 'erase')
                query("DELETE FROM jobs WHERE id='$eid'");
        }
        

        echo "<script type=\"text/javascript\">";
        echo '<!--
            function chkFormular() {
                if(document.job.earbeit.value == "") {
                    alert("Was für eine Arbeit wurde getätigt? Bitte das Feld vollständig ausfüllen");
                    document.job.earbeit.focus();
                    return false;
                }
                if(document.job.ezeit.value == "") {
                    alert("Kein Zeitaufwand eingegeben");
                    document.job.ezeit.focus();
                    return false;
                }
                if(document.job.edatum.value.charAt(2) != "." || document.job.edatum.value.charAt(5) != "." || document.job.edatum.value.length == 9) {
                    alert("Ungültiges Datum eingeben. Bitte so eingeben: Tag.Monat.Jahr(vierstellig) zB. 01.09.2002");
                    document.job.edatum.focus();
                    return false;
                }
            }
            function chkMForm() {
                if(document.mform.emenge.value == "") {
                    alert("Bitte Menge eingeben");
                    document.mform.emenge.focus();
                    return false;
                }
            }
            function ask(url) {
                if(confirm("Wollen Sie wirklich diesen Posten löschen?") == true) window.location.href = url;
            }
            function addtext(text) {
                if(document.job.earbeit.value.charAt(document.job.earbeit.value.length - 1) != " " && document.job.earbeit.value.length != 0)
                    document.job.earbeit.value = document.job.earbeit.value + " ";
                document.job.earbeit.value = document.job.earbeit.value + text + " ";
                document.job.earbeit.focus();
            }
            function chgasuch(text) 
            {
                if(document.asuch)
                    document.asuch.easuche.value = text;
            }
            //-->
            </script>';
	echo '<link type="text/css" rel="stylesheet" href="dhtmlgoodies_calendar/dhtmlgoodies_calendar.css" media="screen"></LINK>';
	echo '<SCRIPT type="text/javascript" src="dhtmlgoodies_calendar/dhtmlgoodies_calendar.js"></script>';
        if($ematerial && $emenge)
        {
            $emenge=strtr($emenge,',','.');
            query("INSERT INTO material SET objekt='$showobjekt', menge='$emenge', artikel='$ematerial', mitarbeiter='$mid', datum=NOW()");
        }
        if($delmaterial)
            query("DELETE FROM material WHERE id='$delmaterial'");
    	$result=query("SELECT o.objekt,o.notiz,k.kunde,k.knr,o.abgeschlossen FROM objekte o,kunden k WHERE o.kunde=k.id AND o.id='$showobjekt'");
    	list($o_objekt,$o_notiz,$o_kunde,$o_kundennr,$abgeschlossen) = mysql_fetch_row($result);
    	if($abgeschlossen=='n') $abgeschlossen=0;
        echo "<b>Kunde:</b> $o_kunde ($o_kundennr)<br> <b>Objekt:</b> $o_objekt";
        echo "<br>\n";
	echo "<b>Notizen (<a href=\"?objektnotizedit=1&showobjekt=$showobjekt\">&auml;ndern</a>):</b> <pre>" . htmlentities($o_notiz) . "</pre><br>";
        echo "<font size=\"4\"><b>Bisher ben&ouml;tigter Aufwand</b></font>\n";
        echo "<table border=\"1\" bordercolor=\"#000000\" cellpadding=\"3\" cellspacing=\"0\">\n";
        echo "<tr><th>Mitarbeiter</th><th>Arbeit</th><th>am</th><th>Aufwand</th></tr>\n";
        $result=query("SELECT j.id,m.id,m.mitarbeiter,DATE_FORMAT(j.datum,'%d.%m.%y'),j.zeit,j.arbeit FROM jobs j, mitarbeiter m WHERE j.mitarbeiter=m.id AND j.objekt='$showobjekt' ORDER BY j.datum ASC");
        $j_mzeit=0;
        $j_azeit=0;
        while(list($j_id,$j_mid,$j_mitarbeiter,$j_datum,$j_zeit,$j_arbeit) = mysql_fetch_row($result))
        {
            if($j_mid == $mid) {
                echo "\n<tr style=\"color:#009900\" onMouseOver=\"this.bgColor='yellow'\" onMouseOut=\"this.bgColor='white'\">";
                $j_mzeit+=$j_zeit;
            }
            else echo "\n<tr style=\"color:#000000\" onMouseOver=\"this.bgColor='orange'\" onMouseOut=\"this.bgColor='white'\">";
            $j_azeit+=$j_zeit;
            $j_zeit=strtr($j_zeit,'.',',');
            echo "<td>$j_mitarbeiter</td><td onClick=\"addtext('$j_arbeit')\">$j_arbeit</a></td><td>$j_datum</td><td>$j_zeit h</td>";
            if($j_mid == $mid) echo "<td><a href=\"javascript:ask('?showobjekt=$showobjekt&jobaktion=erase&eid=$j_id#linie')\">löschen</a>";
            echo "</tr>\n";
        }
        $j_mzeit=strtr($j_mzeit,'.',',');
        $j_azeit=strtr($j_azeit,'.',',');
        echo "<tr><td colspan=3><font color=\"#009900\">Eigener Aufwand gesamt</font></td><td><font color=\"#009900\">$j_mzeit h</font></td></tr>\n";
        echo "<tr><td colspan=3>Aufwand gesamt</td><td>$j_azeit h</td></tr>\n";
        echo "</table>\n";
        echo "<table><tr><td>";
        echo "<form name=\"job\" action=\"$PHP_SELF#linie\" method=\"POST\" onSubmit=\"return chkFormular()\">\n";
        echo "<input type=\"hidden\" name=\"showobjekt\" value=\"$showobjekt\">\n";
        echo "<input type=\"hidden\" name=\"jobaktion\" value=\"insert\">\n";
        echo "<br><table>\n";
        echo "<tr><td>Datum</td><td><input name=\"edatum\" size=\"10\" value=\"" . date("d.m.Y") . "\">";
	echo "<input type=\"button\" value=\"Kalender\" onclick=\"displayCalendar(document.forms[0].edatum,'dd.mm.yyyy',this)\">";
	echo "</td></tr>\n";
        echo "<tr><td>Kunde</td><td>$o_kunde($o_kundennr)</td></tr>\n";
        echo "<tr><td>Objekt</td><td>$o_objekt</td></tr>\n";
        echo "<tr><td>Mitarbeiter</td><td>$mitarbeiter</td></tr>\n";        
        echo "<tr><td valign=top>Arbeit</td><td><textarea name=\"earbeit\" rows=\"3\" cols=\"36\"></textarea></td></tr>\n";
        echo "<tr><td>Zeitaufwand:</td><td><input name=\"ezeit\" size=\"4\"> Stunden</td></tr>\n";
        echo "<tr><td colspan=2 align=right><input type=\"submit\" value=\"Eintragen\"></td></tr>\n";
        echo "</table>\n";
        echo "</form>\n";
        echo "</td><td>";
        $maxzeilen=5;
        $result=query("SELECT qword FROM qwords ORDER BY qword");
        $maxspalten=ceil(mysql_num_rows($result) / $maxzeilen);
        echo "<table border>\n";
        for($n1=0;$n1!=$maxzeilen;$n1++)
        {
            echo "<tr>\n";
            for($n2=0;$n2!=$maxspalten;$n2++)
            {
                list($qword) = mysql_fetch_row($result);
                echo "\t<td><a href=\"javascript:addtext('$qword')\">$qword</a></td>\n";
            }
            echo "</tr>\n";
        }
        echo "</table>";
        echo "</td></tr></table>";

        echo "<a name=\"linie\"></a><hr color=\"#000000\">\n";
        echo "<b>Kunde:</b> $o_kunde ($o_kundennr)<br> <b>Objekt:</b> $o_objekt<br>";
	echo "<b>Notizen (<a href=\"?objektnotizedit=1&showobjekt=$showobjekt\">&auml;ndern</a>):</b> <pre>" . htmlentities($o_notiz) . "</pre>";
	echo "<hr color=\"#000000\">\n";
        /* -------------------------------- */        
        echo "<font size=\"4\"><b>Bisher benötigte Materialien</b></font>\n";
        echo "<table border=\"1\" bordercolor=\"#000000\" cellpadding=\"3\" cellspacing=\"0\">\n";
        echo "<tr><th>Menge</th><th>A-Nummer</th><th>Artikel</th><th>Eingefügt von</th><th>am</th></tr>\n";
        $result=query("SELECT ma.id,ma.menge,a.anr,a.artikel,mi.id,mi.mitarbeiter,DATE_FORMAT(ma.datum,'%d.%m.%y') FROM material ma, artikel a,mitarbeiter mi WHERE ma.artikel=a.id AND ma.mitarbeiter=mi.id AND objekt='$showobjekt' ORDER BY ma.datum");
        while(list($ma_id,$ma_menge,$ma_anr,$ma_artikel,$ma_mid,$ma_mitarbeiter,$datum)=mysql_fetch_row($result))
        {
            $ma_menge=strtr($ma_menge,'.',',');
            echo "<tr onMouseOver=\"this.bgColor='orange'\" onMouseOut=\"this.bgColor='white'\"><td>$ma_menge</td><td onClick=\"chgasuch('$ma_anr')\">$ma_anr</td><td>$ma_artikel</td><td>$ma_mitarbeiter</td><td>$datum</td>";
            if($ma_mid==$mid) echo "<td><a href=\"javascript:ask('?showobjekt=$showobjekt&delmaterial=$ma_id')\">löschen</a></td>";
            echo "</tr>\n";
    	}
        echo "</table>\n<a name=\"material\"></a>";
        if(isset($easuche) || $neweascuhe) {
            if(strlen($easuche) >= 3)
                $result=query("SELECT id,anr,artikel FROM artikel WHERE anr LIKE '%$easuche%' OR artikel LIKE '%$easuche%' ORDER BY anr;");
            else
                $result=query("SELECT id,anr,artikel FROM artikel WHERE anr LIKE '$easuche%' ORDER BY anr;");
            if(mysql_num_rows($result)) {
                echo "<form name=\"mform\" action=\"$PHP_SELF#material\" method=\"POST\" onSubmit=\"return chkMForm()\">\n";
                echo "<input type=\"hidden\" name=\"showobjekt\" value=\"$showobjekt\">\n";
                echo "<input name=\"emenge\" size=\"3\">\n";
                echo "<select name=\"ematerial\">\n";
                while(list($a_id,$a_anr,$a_artikel)=mysql_fetch_row($result))
                    echo "<option value=\"$a_id\">$a_anr $a_artikel\n";
                echo "</select><br>\n";
                echo "<input type=\"submit\" value=\"hinzufügen\">\n";
            } else
                echo "<br>Nichts gefunden \n";
            echo " <a href=\"?showobjekt=$showobjekt&neweasuche=1#material\">Neue Suche</a>\n";
            echo " <a href=\"?showobjekt=$showobjekt&newartikel=1\">Neuen Artikel anlegen</a><br><br>\n";
            if(mysql_num_rows($result)) echo "</form>\n";
        } else {
            echo "<form action=\"$PHP_SELF#material\" method=\"GET\" name=\"asuch\">\n";
            echo "<input type=\"hidden\" name=\"showobjekt\" value=\"$showobjekt\">\n";
            echo "Suchbegriff: <input type=\"text\" name=\"easuche\">\n";
            echo "<input type=\"submit\" value=\"suchen\">\n";
            echo "</form>\n";
        }
        echo "<br>\n";
        
        echo "<a href=\"?selectkunde=1\">Zurück zur Kunden- / Objektauswahl</a>\n";
        echo "<br><br><br><br>\n";        
        foot();
    }

    echo "<h3>Bereits erfasste Arbeit von $mitarbeiter</h3>";
        for($loop=1;$loop!=5;$loop++) {
            switch ($loop) {
                case 1:
                    echo "<h4>Heute (" . date("d.m.Y") . ")</h4>";
                    $result=query("SELECT k.kunde,o.objekt,j.zeit FROM jobs j,kunden k,objekte o WHERE j.objekt=o.id AND o.kunde=k.id AND j.mitarbeiter='$mid' AND j.datum=date(NOW())");
                    break;
                case 2:
                    echo "<h4>Gestern (" . date("d.m.Y", strtotime ("-1 day")) . ")</h4>";
                    $result=query("SELECT k.kunde,o.objekt,j.zeit FROM jobs j,kunden k,objekte o WHERE j.objekt=o.id AND o.kunde=k.id AND j.mitarbeiter='$mid' AND j.datum=date(NOW() - INTERVAL 1 DAY)");
                    break;
                case 3:
                    echo "<h4>Diesen Monat</h4>";
                    $result=query("SELECT k.kunde,o.objekt,SUM(j.zeit) FROM jobs j,kunden k,objekte o WHERE j.objekt=o.id AND o.kunde=k.id AND j.mitarbeiter='$mid' AND EXTRACT(YEAR_MONTH FROM j.datum)=EXTRACT(YEAR_MONTH FROM NOW() - INTERVAL 0 MONTH) GROUP BY j.objekt ORDER BY k.kunde");
                    break;
                case 4:
                    echo "<h4>Vormonat</h4>";
                    $result=query("SELECT k.kunde,o.objekt,SUM(j.zeit) FROM jobs j,kunden k,objekte o WHERE j.objekt=o.id AND o.kunde=k.id AND j.mitarbeiter='$mid' AND EXTRACT(YEAR_MONTH FROM j.datum)=EXTRACT(YEAR_MONTH FROM NOW() - INTERVAL 1 MONTH) GROUP BY j.objekt ORDER BY k.kunde");
                    break;
                default:
            }
            if(mysql_num_rows($result)) {
                echo "<table bordercolor=\"#000000\" border=1 cellpadding=3 cellspacing=0 width=\"800\">";        
                echo "<tr><th>Kunde</th><th>Objekt</th><th>Zeit</th></tr>";
                $g_zeit=0;
                    while(list($j_kunde,$j_objekt,$j_zeit) = mysql_fetch_row($result)) {
                        $g_zeit+=$j_zeit;
                        echo "<tr onMouseOver=\"this.bgColor='orange'\" onMouseOut=\"this.bgColor='white'\"><td>$j_kunde</td><td>$j_objekt</td><td nowrap>$j_zeit h</td></tr>";
                    }
                echo "<tr><td colspan=2><b>Gesamt</td><td nowrap>$g_zeit h</td></tr>";
                echo "</table>";
            } else {
                echo "Keine Eintragungen";
            }

        }


    echo "<br><a href=\"$PHP_SELF?mid=$mid&selectkunde=1\">Arbeit/Material hinzufügen</a><br><br>";
foot();
?>
