<?php
    include "include/inc_base.php";
	$cookie_name = "MEMLOGIN_ADMIN_PURUN";
    
	
	SetCookie("MEMLOGIN_ADMIN_PURUN",'',0,'/',$c_domain);
 
?>
<script>
    top.location.href = "login.php";
</script>