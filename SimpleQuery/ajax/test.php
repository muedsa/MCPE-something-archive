<?
if(isset($_GET['a']) AND isset($_GET['b']) AND is_numeric($_GET['a']) AND is_numeric($_GET['b'])){
	echo "A+B=".$a+$b;
}else{
	echo "0";
}
?>