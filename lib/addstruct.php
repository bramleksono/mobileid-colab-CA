<?php

//Load Address
$clientaddr = json_decode(file_get_contents($addressfile));
$CAaddr = $clientaddr->CA;
$CAuserreg = $CAaddr."/user/reg";
$CAuserregcheck = $CAaddr."/user/regcheck";
$CAuserregconfirm = $CAaddr."/user/regconfirm";
$CAmessaging = $CAaddr."/message";
$CAlogin = $CAaddr."/login";
$CAloginconfirm = $CAaddr."/login/confirm";

$SIaddr = $clientaddr->SI;
$SIuserreg = $SIaddr."/user/reg";
$SImessaging = $SIaddr."/message";
$SIlogin = $SIaddr."/login";
$SIloginconfirm = $SIaddr."/login/confirm";