<?php

//Load Address
$clientaddr = json_decode(file_get_contents($addressfile));
$CAaddr = $clientaddr->CA;
$CAuserreg = $CAaddr."/user/reg";

$SIaddr = $clientaddr->SI;
$SIuserreg = $SIaddr."/user/reg";
