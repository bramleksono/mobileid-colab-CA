<?php

$app->get('/verify/:idnumber', function ($idnumber) {
   echo "Hello ".$idnumber; 
   $user = new CAuser($idnumber);
   $registered = $user->isRegistered();
   echo $registered;
   
   //$userinfo  =$user->getUserDevice();
   //var_dump($userinfo);
});