<?php
include_once("../session.config.php");
include_once("../db.config.php");
include_once("../lxpj.php");
include_once("../taisho/ak.php");

$db = new DBConnection();
$dbConn = $db->getConnection();

$sakstses = get_new_sakstses();
$rid      = requestValue("rid");
$fenlei   = requestValue("fenlei");
$dakname  = requestValue("dakname");
$sakno    = requestValue("sakno");
$opt      = requestValue("opt");

$mainMenu = getMainMenuStr($_SESSION["AryOfAllMenus"]['mainmenu'] , "数据収集" );
$subMenu  = getSubMenuStr( $_SESSION["AryOfAllMenus"]['数据収集'] , "私の案件" );

if("unlock" == $opt) {
    $sql = "update saks"
	      ." set lksha = null, lktime = null"
		  ." where rowid=chartorowid('" . $rid . "')";
	$stmt = oci_parse($dbConn, $sql);
	$result = oci_execute($stmt, OCI_DEFAULT);
	if(!$result) {
		oci_rollback($dbConn);
		$db->closeConnection();
        print_error($mainMenu, $subMenu, "エラー、更新できません！<br>" . $sql);
        exit(0);
    }
    $fenlei = "";
    $dakname = "";
    $sakno = null;
}

?>
<html>
<head>
<meta http-equiv="x-ua-compatible" content="ie=6">
<meta http-equiv="content-type" content="text/html;charset=UTF-8">
<title><?php echo $app["PJ_TITLE"]; ?></title>
<link href="../lxpj.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php
echo $mainMenu;
echo $subMenu;
?>
<center>
<table cellspacing="0" cellpadding="0" border="0" width="96%">
<caption align="left">◆未完了の案件一覧</caption>
<?php
class Df {
    public $fenlei;
    public $daks;
    public $row_size;
    function Df($fenlei) {
        $this->fenlei = $fenlei;
        $this->daks = array();
        $this->row_size = 0;
    }
}
class Dak {
    public $dakname;
    public $saks;
    function Dak($dakname) {
        $this->dakname = $dakname;
        $this->saks = array();
    }
}
class SakDetailClass {
	public $rid=null;
	public $fenlei=null;
	public $dakname=null;
    public $sakno=null;
    public $sakname=null;
    public $cdtt=null;
    public $cdrv=null;
	public $lksha=null;
	function SakDetailClass(){
		$this->rid="";
		$this->fenlei="";
		$this->dakname="";
		$this->sakno="";
		$this->sakname="";
		$this->cdtt="";
		$this->cdrv="";
		$this->lksha="";
	}
}

$sql = " select distinct rowidtochar(s.rowid) rid,s.fenlei,s.dakname,s.sakno ".
	   " , s.sakname, tt.mname cdtt, rv.mname cdrv, s.lksha, f.bsindex ".
       " from saks s, tasks t, members tt, members rv, fenleis f " .
       " where s.fenlei=t.fenlei and s.dakname=t.dakname and s.sakno=t.sakno". 
	   " and s.cdtt=tt.mid and s.cdrv=rv.mid".
	   " and f.fenlei=s.fenlei".
	   " and s.fenlei not in ('維持','会社','休暇','自習')".
	   " and (t.sts='未着手' or t.sts='対応中')" .
       " and (t.cdtt='" . $_SESSION["auth_user"]."' or t.cdrv='".$_SESSION["auth_user"]."')".
       " order by f.bsindex, s.fenlei,s.sakno";

$aryFenleiRowSpan  = array();
$aryDaknameRowSpan = array();
$aryFenleis    = array();
$aryDaknames   = array();
$arySakDetails = array();
$fenlei = null;
$dakname = null;
$isaknoCount=0;
$ifenleiCount=0;
$idaknameCount=0;
$stmt = oci_parse($dbConn, $sql);
$result = oci_execute($stmt);
if ( $row = oci_fetch_object($stmt) ) {
	do{
		$sak = new SakDetailClass();
		$sak->rid     = $row->RID;
		$sak->fenlei  = $row->FENLEI;
		$sak->dakname = $row->DAKNAME;
		$sak->sakno   = $row->SAKNO;
		$sak->sakname = $row->SAKNAME;
		$sak->cdtt    = $row->CDTT;
		$sak->cdrv    = $row->CDRV;
		$sak->lksha   = $row->LKSHA;
		$arySakDetails[$isaknoCount++] = $sak;
		$ifenleiCount++;
		$idaknameCount++;
		
		if($fenlei!= $row->FENLEI ) {
			array_push($aryFenleis, $row->FENLEI);
			array_push($aryDaknames, $row->DAKNAME);
		} else if($dakname != $row->DAKNAME ) {
			array_push($aryDaknames, $row->DAKNAME);
		}
/*
		if($fenlei != null && $fenlei != $row->FENLEI) {
			array_push($aryFenleiRowSpan,$ifenleiCount);
			array_push($aryFenleis,$fenlei);
			$ifenleiCount = 0;
			array_push($aryDaknameRowSpan,$idaknameCount);
			array_push($aryDaknames,$dakname);
			$idaknameCount = 0;
		} else {
			$ifenleiCount++;
			if($dakname != null && $dakname != $row->DAKNAME) {
				array_push($aryDaknameRowSpan,$idaknameCount);
				array_push($aryDaknames,$dakname);
				$idaknameCount = 0;
			} else {
				$idaknameCount++;
			}
		}
*/		
	} while($row = oci_fetch_object($stmt));
	array_push($aryDaknames, $row->DAKNAME);
}

$sak_count = count($arySakDetails);

$len = count($aryFenleis);
for ($k=0; $k<$len; $k++){
	echo ( "[".$aryFenleis[$k]."](".$aryFenleiRowSpan[$k].")" );
	echo ( "<br>");
}
$len = count($aryDaknames);
for ($k=0; $k<$len; $k++){
	echo ( "[".$aryDaknames[$k]."](".$aryDaknameRowSpan[$k].")" );
	echo ( "<br>");
}

?>
</table>
</center>
</body>
</html>
<?php
$db->closeConnection();
?>
