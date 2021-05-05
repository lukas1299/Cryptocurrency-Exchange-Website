<?php
    require_once "dataBaseConnector.php";
    session_start();

    if(!isset($_POST['amount']) || !isset($_POST['buy']) || !isset($_POST['pay'])){
        header("Location:userProfile.php");
        exit();
    }

    mysqli_report(MYSQLI_REPORT_STRICT);
    $connection = new mysqli($host, $db_user, $db_password, $db_name);

    if ($_POST['pay'] == 'myWallet'){
        for ($i = 0; $i < sizeof($_SESSION['krypto']); $i++){
            if ($_POST['buy'] == $_SESSION['krypto'][$i][1]){
                $toPay = $_POST['amount'] * $_SESSION['krypto'][$i][2];

                if ($toPay <= $_SESSION['portfel'][0][2]){

                    //szukanie czy jest lista z taka krypto
                    $exist = false;
                    for ($x = 0; $x < sizeof($_SESSION['lista_walut']); $x++){
                        if ($_SESSION['lista_walut'][$x][2] == $_SESSION['krypto'][$i][0]){
                            if ($connection->connect_errno != 0) {
                                throw new Exception(mysqli_connect_error());
                            }else {
                                $connection->query("UPDATE lista_walut SET ilość_krypto = '".($_SESSION['lista_walut'][$x][3] + $_POST['amount'])."' WHERE id_krypto = '".$_SESSION['krypto'][$i][0]."'");
                                $connection->query("INSERT INTO transakcje (id_krypto,id_portfela,data_transakcji,czas_zawarcia,ilosc,status,kurs_transakcji) VALUES ('".$_SESSION['krypto'][$i][0]."','".$_SESSION['portfel'][0][0]."','".date("Y-m-d")."','".date("H:i")."','".$_POST['amount']."','"."BOUGHT"."','".$_SESSION['krypto'][$i][2]."')");
                                $exist = true;
                            }
                            break;
                        }
                    }

                    //lista nie istnieje
                    if (!$exist){
                        if ($connection->connect_errno != 0) {
                            throw new Exception(mysqli_connect_error());
                        }else {
                            $connection->query("INSERT INTO  lista_walut (id_portfela, id_krypto, ilość_krypto) VALUES('".$_SESSION['portfel'][0][0]."','".$_SESSION['krypto'][$i][0]."','".$_POST['amount']."')");
                            $connection->query("INSERT INTO transakcje (id_krypto,id_portfela,data_transakcji,czas_zawarcia,ilosc,status,kurs_transakcji) VALUES ('".$_SESSION['krypto'][$i][0]."','".$_SESSION['portfel'][0][0]."','".date("Y-m-d")."','".date("H:i")."','".$_POST['amount']."','"."BOUGHT"."','".$_SESSION['krypto'][$i][2]."')");
                        }
                    }

                    //zabieramy pieniadze z portfela
                    if ($connection->connect_errno != 0) {
                        throw new Exception(mysqli_connect_error());
                    }else {
                        $connection->query("UPDATE portfele SET ilość_euro = '".($_SESSION['portfel'][0][2] - $toPay)."' WHERE id_portfela = '".$_SESSION['portfel'][0][0]."'");
                        $exist = true;
                    }
                }else{
                    $_SESSION['err_fund'] = '<span style = "color:#ff0000">You have not enough money</span><br>';
                    header('Location: userProfile.php');
                }
                $connection->close();
                unset($_POST['amount']);
                unset($_POST['pay']);
                unset($_POST['buy']);
                header('Location: userProfile.php');
            }
        }
    }else{
        for ($i = 0; $i < sizeof($_SESSION['krypto']); $i++) {
            if ($_POST['pay'] == $_SESSION['krypto'][$i][1]) {
                for ($a = 0; $a < sizeof($_SESSION['krypto']); $a++) {
                    if ($_POST['buy'] == $_SESSION['krypto'][$a][1]){
                        $exRatio = $_SESSION['krypto'][$i][2]/$_SESSION['krypto'][$a][2];
                        $toPay = $_POST['amount']/$exRatio;
                        for ($b = 0; $b < sizeof($_SESSION['lista_walut']); $b++){
                            if($_SESSION['lista_walut'][$b][2] == $_SESSION['krypto'][$i][0]){
                                if ($_SESSION['lista_walut'][$b][3] >= $toPay){

                                    //szukanie czy jest lista z taka krypto
                                    $exist = false;
                                    for ($c = 0; $c < sizeof($_SESSION['lista_walut']); $c++){
                                        if ($_SESSION['lista_walut'][$c][2] == $_SESSION['krypto'][$a][0]){
                                            if ($connection->connect_errno != 0) {
                                                throw new Exception(mysqli_connect_error());
                                            }else {
                                                $connection->query("UPDATE lista_walut SET ilość_krypto = '".($_SESSION['lista_walut'][$c][3] + $_POST['amount'])."' WHERE id_krypto = '".$_SESSION['krypto'][$a][0]."'");
                                                $connection->query("INSERT INTO transakcje (id_krypto,id_portfela,data_transakcji,czas_zawarcia,ilosc,status,kurs_transakcji) VALUES ('".$_SESSION['krypto'][$a][0]."','".$_SESSION['portfel'][0][0]."','".date("Y-m-d")."','".date("H:i")."','".$_POST['amount']."','"."SWAPPED OF ".$toPay." ".$_SESSION['krypto'][$i][1].""."','".$_SESSION['krypto'][$a][2]."')");
                                                $exist = true;
                                            }
                                            break;
                                        }
                                    }

                                    //lista nie istnieje
                                    if (!$exist){
                                        if ($connection->connect_errno != 0) {
                                            throw new Exception(mysqli_connect_error());
                                        }else {
                                            $connection->query("INSERT INTO  lista_walut (id_portfela, id_krypto, ilość_krypto) VALUES('".$_SESSION['portfel'][0][0]."','".$_SESSION['krypto'][$a][0]."','".$_POST['amount']."')");
                                            $connection->query("INSERT INTO transakcje (id_krypto,id_portfela,data_transakcji,czas_zawarcia,ilosc,status,kurs_transakcji) VALUES ('".$_SESSION['krypto'][$a][0]."','".$_SESSION['portfel'][0][0]."','".date("Y-m-d")."','".date("H:i")."','".$_POST['amount']."','"."SWAPPED OF ".$toPay." ".$_SESSION['krypto'][$i][1].""."','".$_SESSION['krypto'][$a][2]."')");

                                        }
                                    }

                                    //zabieramy krypto z listy
                                    if ($connection->connect_errno != 0) {
                                        throw new Exception(mysqli_connect_error());
                                    }else {
                                        $connection->query("UPDATE lista_walut SET ilość_krypto = '".($_SESSION['lista_walut'][$b][3] - $toPay)."' WHERE id_krypto = '".$_SESSION['krypto'][$i][0]."'");
                                        $exist = true;
                                    }
                                    unset($_POST['amount']);
                                    unset($_POST['pay']);
                                    unset($_POST['buy']);
                                    header('Location: userProfile.php');
                                    break 3;
                                }else{
                                    $_SESSION['err_fund'] = '<span style = "color:#ff0000">You have not enough assets</span><br>';
                                    unset($_POST['amount']);
                                    unset($_POST['pay']);
                                    unset($_POST['buy']);
                                    header('Location: userProfile.php');
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    //TODO: Odnoszenie do okienka zakupu jesli blad/odnoszenie do userProfile
?>