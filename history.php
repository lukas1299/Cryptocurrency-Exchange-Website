<?php
session_start();
require_once "dataBaseConnector.php";

if (!isset($_SESSION['isLoggedIn'])) {
    header('Location: homePage.php');
    exit();
}
//pobranie informacji o krypto
$ch = curl_init();
$url = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=eur&order=market_cap_desc&per_page=20&page=1&sparkline=false";
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

mysqli_report(MYSQLI_REPORT_STRICT);
$connection = new mysqli($host, $db_user, $db_password, $db_name);

//wpisanie krytpto do bazy/aktualizacja ceny
if ($e = curl_error($ch)) {
    echo $e;
} else {
    $decoded = json_decode($response, true);

    if ($connection->connect_errno != 0) {
        throw new Exception(mysqli_connect_error());
    } else {
        for ($i = 0; $i < sizeof($decoded); $i++) {

            $result = $connection->query("SELECT COUNT(*) FROM kryptowaluty WHERE nazwa = '" . $decoded[$i]['name'] . "'");
            $row = $result->fetch_assoc();

            if ($row['COUNT(*)'] == 0) {
                $connection->query("INSERT INTO kryptowaluty VALUES (" . ($i + 1) . ",'" . $decoded[$i]['name'] . "','" . $decoded[$i]['current_price'] . "')");
            } else {
                $connection->query("UPDATE kryptowaluty SET kurs = '" . $decoded[$i]['current_price'] . "' WHERE nazwa = '" . $decoded[$i]['name'] . "'");
            }
            $result->free();
        }
    }
}
curl_close($ch);

//sprawdzanie ktore krypto posiadamy
if ($connection->connect_errno != 0) {
    throw new Exception(mysqli_connect_error());
} else {
    $result = $connection->query("SELECT * FROM portfele WHERE id_użytkownika = '" . $_SESSION['id_użytkownika'] . "'");
    $_SESSION['portfel'] = $result->fetch_all();
    $result->free();

    $result = $connection->query("SELECT * FROM lista_walut WHERE id_portfela = '" . $_SESSION['portfel'][0][0] . "'");
    $_SESSION['lista_walut'] = $result->fetch_all();
    $result->free();
}

//pobranie listy krypto dostepnej w bazie
if ($connection->connect_errno != 0) {
    throw new Exception(mysqli_connect_error());
} else {
    $result = $connection->query("SELECT * FROM kryptowaluty ");
    $_SESSION['krypto'] = $result->fetch_all();
    $result->free();
}

//pobranie informacji o transakcjach
if ($connection->connect_errno != 0) {
    throw new Exception(mysqli_connect_error());
} else {
    $sql = "SELECT k.nazwa as name, t.data_transakcji as date, t.czas_zawarcia as time, t.ilosc as amount, t.status as stat, kurs_transakcji as course FROM 
    kryptowaluty k, transakcje t WHERE t.id_krypto=k.id_krypto ORDER BY data_transakcji DESC, czas_zawarcia DESC" ;

    $result = $connection->query($sql);
    $_SESSION['transakcje'] = $result->fetch_all();
    $result->free();
}

$connection->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/yourcode.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <title>userProfile</title>
    <!-- bootstrap 5 css -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha2/css/bootstrap.min.css" integrity="sha384-DhY6onE6f3zzKbjUPRc2hOzGAdEf4/Dz+WJwBvEYL/lkkIsI3ihufq9hk9K4lVoK" crossorigin="anonymous">
    <!-- custom css -->
    <link rel="stylesheet" href="userProfileStyle.css">
</head>

<body>

    <nav class="navbar navbar-expand d-flex flex-column align-item-start" id="sidebar">
        <a href="#" class="navbar-brand text-light mt-5">

            <img src="img/oct.png" style="width: 240px; height: 170px; margin-left:3%" alt="niema">
        </a>
        <ul class="navbar-nav d-flex flex-column mt-5 w-100">
            <li class="nav-item w-100">
                <a href="home.php" class="nav-link text-light pl-4"><img src="img/home.png">   Home</a>
            </li>
            <li class="nav-item w-100">
                <a href="wallet.php" class="nav-link text-light pl-4"><img src="img/wallet.png">   Wallet</a>
            </li>
            <li class="nav-item w-100">
                <a href="buyOrSell.php" class="nav-link text-light pl-4"><img src="img/buy.png">   Buy/Sell</a>
            </li>
            <li class="nav-item w-100">
                <a href="#" class="nav-link text-light pl-4"><img src="img/exchange.png">   Exchange</a>
            </li>
            <li class="nav-item w-100">
                <a href="history.php" class="nav-link text-light pl-4"><img src="img/history.png">   History</a>
            </li>
            <li class="nav-item w-100">
                <a href="#" class="nav-link text-light pl-4"><img src="img/post.png">   Contact</a>
            </li>
        </ul>
    </nav>

    <!-- History section -->

    <section id="wallet" class="p-4 my-container">
        <div style="display: inline">
            <script src="https://widgets.coingecko.com/coingecko-coin-price-marquee-widget.js"></script>
            <coingecko-coin-price-marquee-widget coin-ids="bitcoin,ethereum,litecoin,ripple" currency="usd" background-color="#ffffff" locale="en"></coingecko-coin-price-marquee-widget>
            <h2>History of tearing</h2>

            <form action="/20-21-ai-projekt-lab3-projekt-ai-koscielniak-b-matusik-l/logOut.php">
                <button type="submit" class="btn btn-outline-danger" data-mdb-ripple-color="dark" style=" float:right; margin-right:10px">
                    Log Out
                </button>
                <button type="button" class="btn btn-outline-primary btn-rounded" data-mdb-ripple-color="dark" style="float:right; margin-right:10px" data-target="#myModal" data-toggle="modal">
                    Buy/Sell
                </button>
                <h1 style=" float:right; margin-right:20px">
                    <?php
                    echo '<a>' . $_SESSION['portfel'][0][2] . '</a>'
                    ?>
                    <a style="font-size: 20px;">€</a>
                </h1>

            </form>

        </div>

        <div class="container">

            <!-- Trigger the modal with a button -->

            <!-- Modal -->
            <!--Modal: Login / Register Form-->
            <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog cascading-modal" role="document">
                    <!--Content-->
                    <div class="modal-content">

                        <!--Modal cascading tabs-->
                        <div class="modal-c-tabs">

                            <!-- Nav tabs -->
                            <ul class="nav nav-tabs md-tabs tabs-2 light-blue darken-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#panel7" role="tab">
                                        Buy</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#panel8" role="tab">
                                        Sell</a>
                                </li>
                            </ul>

                            <!-- Tab panels -->
                            <div class="tab-content">
                                <!--Buy tab-->
                                <div class="tab-pane fade in show active" id="panel7" role="tabpanel">

                                    <!--Body-->
                                    <div class="modal-body mb-1">
                                        <div class="md-form form-sm mb-5">
                                            
                                            <label data-error="wrong" data-success="right" for="modalLRInput12" class="wallet-val">Your wallet</label>
                                            <h1 id="euro-amount">
                                                <?php
                                                echo '<a>' . $_SESSION['portfel'][0][2] . '</a>'
                                                ?>
                                                <a style="font-size: 20px;">€</a>
                                            </h1>
                                        </div>

                                        <div class="md-form form-sm mb-4">
                                            <label data-error="wrong" data-success="right" for="modalLRInput12" class="wallet-val">Buy:</label>

                                            <form action="buyCrypto.php" method="post" class="mb-3">
                                                <select name="buy" id="crypto" class="form-select" aria-label="Default select example">

                                                    <?php
                                                    for ($i = 0; $i < 10; $i++) {
                                                        echo '<option value="' . htmlspecialchars($_SESSION['krypto'][$i][1]) . '" >' . $_SESSION['krypto'][$i][1] . '</option>' . "\n";
                                                    }
                                                    ?>

                                                </select>
                                                <label data-error="wrong" data-success="right" for="modalLRInput12" class="wallet-val">Pay:</label>
                                                <select name="pay" id="crypto" class="form-select" aria-label="Default select example">

                                                    <option value="myWallet">My Wallet</option>
                                                    <?php
                                                    for ($i = 0; $i < sizeof($_SESSION['lista_walut']); $i++) {
                                                        for ($a = 0; $a < sizeof($_SESSION['krypto']); $a++) {
                                                            if ($_SESSION['lista_walut'][$i][2] == $_SESSION['krypto'][$a][0] && $_SESSION['lista_walut'][$i][3] > 0) {
                                                                echo '<option value="' . htmlspecialchars($_SESSION['krypto'][$a][1]) . '" >' . $_SESSION['krypto'][$a][1] . '(' . $_SESSION['lista_walut'][$i][3] . ')</option>' . "\n";
                                                                break;
                                                            }
                                                        }
                                                    }

                                                    ?>
                                                </select>
                                                <label data-error="wrong" data-success="right" for="modalLRInput12" class="wallet-val">How much?</label>
                                                <div class="input-group mb-3">
                                                    <input id="amountInput" name="amount" type="text" class="form-control" onkeypress="return onlyNumberKey(event)" autocomplete="off">
                                                   
                                                </div>
                                                <textarea name="area" style="display:none">history.php</textarea>
                                                <button id="submitButton" type="submit" class="btn btn-outline-primary btn-rounded" data-mdb-ripple-color="dark">Buy</button>
                                            </form>

                                        </div>
                                    </div>
                                    <!--Footer-->
                                    <div class="modal-footer">
                                        <p>Powered by</p>

                                        <img src="img/mastercard.png" width="60px" height="60px">
                                        <button type="button" class="btn btn-outline-primary btn-rounded" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                                <!--/.Buy tab-->

                                <!--Sell tab-->
                                <div class="tab-pane fade" id="panel8" role="tabpanel">

                                    <div class="modal-body">
                                        <div class="md-form form-sm mb-5">
                                           

                                            <label data-error="wrong" data-success="right" for="modalLRInput12" class="wallet-val">Your wallet</label>
                                            <h1 id="euro-amount">
                                                <?php
                                                echo "<script>console.log('Debug Objects:');</script>";
                                                echo '<a>' . $_SESSION['portfel'][0][2] . '</a>'
                                                ?>
                                                <a style="font-size: 20px;">€</a>
                                            </h1>

                                           

                                        </div>

                                        <div>

                                            <label data-error="wrong" data-success="right" for="modalLRInput12" class="wallet-val">Sell</label>

                                            <form action="sellCrypto.php" method="post" class="mb-3">

                                                <select id="toSell" name="sell" class="form-select" aria-label="Default select example">

                                                    <?php
                                                    $temp = 0;
                                                    for ($i = 0; $i < sizeof($_SESSION['lista_walut']); $i++) {
                                                        for ($a = 0; $a < sizeof($_SESSION['krypto']); $a++) {
                                                            if ($_SESSION['lista_walut'][$i][2] == $_SESSION['krypto'][$a][0] && $_SESSION['lista_walut'][$i][3] > 0) {
                                                                echo '<option id="'.$_SESSION['lista_walut'][$i][3].'" value="' . htmlspecialchars($_SESSION['krypto'][$a][0]) . '" >' . $_SESSION['krypto'][$a][1] . '(' . $_SESSION['lista_walut'][$i][3] . ')</option>' . "\n";
                                                                $temp += 1;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    if ($temp == 0) {
                                                        echo "No assets to sell";
                                                    }
                                                    ?>
                                                </select>

                                                <label data-error="wrong" data-success="right" for="modalLRInput12" class="wallet-val">How much?</label>

                                                <div class="input-group mb-3">
                                                    <input id="amountInputSell" name="amount" type="text" class="form-control" onkeypress="return onlyNumberKey(event)" autocomplete="off">
                                                    <div class="input-group-append">
                                                        <button id="maxButton" class="btn btn-outline-primary" type="button" onclick="sendMax()">MAX</button>
                                                    </div>
                                                </div>

                                                <?php if (isset($_SESSION['err_fund2'])) {
                                                    echo $_SESSION['err_fund2'];
                                                    unset($_SESSION['err_fund2']);
                                                } ?>
                                                <textarea name="area" style="display:none">history.php</textarea>
                                                <button id="submitButtonSell" type="submit" class="btn btn-outline-primary btn-rounded" data-mdb-ripple-color="dark">Sell</button>
                                            </form>
                                        </div>

                                    </div>
                                    <!--Footer-->
                                    <div class="modal-footer">
                                        <p>Powered by</p>
                                        <img src="img/mastercard.png" width="60px" height="60px">
                                        <button type="button" class="btn btn-outline-primary btn-rounded" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                                <!--/.Sell tab-->
                            </div>
                        </div>
                    </div>
                    <!--/.Content-->
                </div>
            </div>
            <!--Modal: Login / Register Form-->
        </div>
        <button class="btn my-4" id="menu-btn">Menu</button>


        <div class="main-content">
            <div class="container mt-7">
                <!-- Table -->

                <div class="row">

                    <div class="col">
                        <div class="card shadow">
                            <div class="card-header border-0">
                                <h3 class="mb-0">History</h3>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-items-center table-flush">
                                    <thead class="thead-light">
                                        <tr>
                                            <th scope="col">Name</th>
                                            <th scope="col">Date</th>
                                            <th scope="col">Time</th>
                                            <th scope="col">Amount</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Course</th>

                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php

                                        for ($i = 0; $i < sizeof($_SESSION['transakcje']); $i++) {

                                            echo '<tr>
                                            <th scope="row">
                                                <div class="media align-items-center">
                                                    <div class="media-body">
                                                        <span class="mb-0 text-sm">' . $_SESSION['transakcje'][$i][0] . '</span>
                                                    </div>
                                                </div>
                                            </th>
                                            <td>'
                                                . $_SESSION['transakcje'][$i][1] .
                                                '</td>
                                            <td>'
                                                . $_SESSION['transakcje'][$i][2] .
                                                '</td>
                                            
                                            <td>'
                                                . $_SESSION['transakcje'][$i][3] .
                                                '</td>
                                            
                                            <td>'
                                                . $_SESSION['transakcje'][$i][4] .
                                                '</td>
                                            
                                            <td>'
                                                . $_SESSION['transakcje'][$i][5] .
                                                '</td>
                                            
                                            </tr>';
                                        }
                                        if ($temp == 0) {
                                            echo '<tr><th>Looks like there is no assets associated with your wallet. Add some funds and start Your journey</th></tr>' . "\n";
                                        } //TODO:FRONTEND: Alert ma sie wyswietlac na calej szerokosci tabeli TML <nav aria-label="Page navigation example">

                                        ?>
 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>


    <!-- bootstrap js -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.0.0-alpha2/js/bootstrap.min.js" integrity="sha384-5h4UG+6GOuV9qXh6HqOLwZMY4mnLPraeTrjT5v07o347pj6IkfuoASuGBhfDsp3d" crossorigin="anonymous"></script>
    <!-- custom js -->
    <script>
        var menu_btn = document.querySelector("#menu-btn")
        var sidebar = document.querySelector("#sidebar")
        var container = document.querySelector(".my-container")
        menu_btn.addEventListener("click", () => {
            sidebar.classList.toggle("active-nav")
            container.classList.toggle("active-cont")
        })

        function onlyNumberKey(evt) {
            var ASCIICode = (evt.which) ? evt.which : evt.keyCode
            if (ASCIICode > 31 && (ASCIICode < 48 || ASCIICode > 57) && ASCIICode != 46) {
                validateBuy();
                return false;
            }else{
                validateBuy();
                return true;
            }
        }

        function validateBuy(){
            var input = document.getElementById('amountInput').value;
            const button = document.getElementById('submitButton');

            if(input > 0){
                button.disabled = false;
            }else {
                button.disabled = true;
            }
        }
        setInterval(validateBuy,250);

        function validateSell(){
            var input = document.getElementById('amountInputSell').value;
            const button = document.getElementById('submitButtonSell');

            if(input > 0){
                button.disabled = false;
            }else {
                button.disabled = true;
            }
        }

        function sendMax(max){
            document.getElementById('maxButton').onclick = function () {
                var e =document.getElementById('toSell');
                document.getElementById('amountInputSell').value = e.options[e.selectedIndex].id;}
        }
        setInterval(validateSell,250);
    </script>
</body>

</html>