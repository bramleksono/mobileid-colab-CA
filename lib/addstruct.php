<?php

//Load Address
$clientaddr = json_decode(file_get_contents($addressfile));
$CAaddr = $clientaddr->CA;
$CAuserinitial = $CAaddr."/user/initial";
$CAuserreg = $CAaddr."/user/reg";
$CAuserregcheck = $CAaddr."/user/regcheck";
$CAuserregconfirm = $CAaddr."/user/regconfirm";
$CAmessaging = $CAaddr."/message";
$CAlogin = $CAaddr."/login";
$CAloginconfirm = $CAaddr."/login/confirm";
$CAverify = $CAaddr."/verify";
$CAverifyconfirm = $CAaddr."/verify/confirm";
$CAcreatemessagesig = $CAaddr."/createsig";
$CAverifymessagesig = $CAaddr."/verifysig";

$SIaddr = $clientaddr->SI;
$SIuserreg = $SIaddr."/user/reg";
$SImessaging = $SIaddr."/message";
$SIlogin = $SIaddr."/login";
$SIloginconfirm = $SIaddr."/login/confirm";
$SIverify = $SIaddr."/verify";
$SIverifyconfirm = $SIaddr."/verify/confirm";

$Webaddr = $clientaddr->Web;
$Webloginconfirm = $Webaddr."/process/confirm";