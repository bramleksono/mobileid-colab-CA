<?php

//Load Address
$clientaddr = json_decode(file_get_contents($addressfile));
$CAaddr = $clientaddr->CA;
$CAuserreg = $CAaddr."/user/reg";
$CAuserregcheck = $CAaddr."/user/regcheck";
$CAuserregconfirm = $CAaddr."/user/regconfirm";

$SIaddr = $clientaddr->SI;
$SIuserreg = $SIaddr."/user/reg";