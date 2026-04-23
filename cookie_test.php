<?php
setcookie("teste_cookie", "ok", time()+3600, "/");

if(isset($_COOKIE['teste_cookie'])){
    echo "COOKIE FUNCIONA";
} else {
    echo "COOKIE NAO FUNCIONA";
}