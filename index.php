<?php
$priceData = json_decode(file_get_contents("pricedata.json"));

$fmt = numfmt_create('de_DE', NumberFormatter::CURRENCY);

usort($priceData, function ($a, $b) {
	return $a->price > $b->price;
});

function timeAgo($time)
{
	$dt = new DateTime();
	$dt->setTime(0, 0, 0);
	$seconds_ago = ($dt->getTimestamp() - $time);

	if ($seconds_ago >= 31536000) {
		$ago = intval($seconds_ago / 31536000);
		return "Vor " . $ago . " Jahr" . ($ago > 1 ? "en" : "");
	} elseif ($seconds_ago >= 2419200) {
		$ago = intval($seconds_ago / 2419200);
		return "Vor " . $ago . " Monat" . ($ago > 1 ? "en" : "");
	} elseif ($seconds_ago >= 86400) {
		$ago = intval($seconds_ago / 86400);
		return "Vor " . $ago . " Tag" . ($ago > 1 ? "en" : "");
	} elseif ($seconds_ago >= 3600) {
		$ago = intval($seconds_ago / 3600);
		return "Vor " . $ago . " Stunde" . ($ago > 1 ? "n" : "");
	} elseif ($seconds_ago >= 60) {
		$ago = intval($seconds_ago / 60);
		return "Vor " . $ago . " Minute" . ($ago > 1 ? "n" : "");
	} else {
		return "<span style='color:#FF0000;'>JETZT</span>";
	}
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Table V01</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!--===============================================================================================-->
    <link rel="icon" type="image/png" href="assets/images/icons/favicon.ico"/>
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/bootstrap/css/bootstrap.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/animate/animate.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/select2/select2.min.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/vendor/perfect-scrollbar/perfect-scrollbar.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="assets/css/util.css">
    <link rel="stylesheet" type="text/css" href="assets/css/main.css">
    <!--===============================================================================================-->
    <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.bundle.min.js"></script>

    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
</head>
<body>

<div class="limiter">
    <div class="container-table100">
        <div class="wrap-table100">
            <div class="table100">
                <table>
                    <thead>
                    <tr class="table100-head">
                        <!--<th class="column1">Produkt ID</th>-->
                        <th class="column2">Bild</th>
                        <th class="column3">Name</th>
                        <th class="column4">Preis</th>
                        <th class="column5">Preiskurve</th>
                        <th class="column6">Preisdatum</th>
                        <th class="column7">Aktualisiert</th>
                        <th class="column8">Anbieter</th>
                    </tr>
                    </thead>
                    <tbody>
					<?php
					$totalPrice = 0;
					$i = 0;
					foreach ($priceData as $product) {
						$xArr = [];
						$yArr = [];
						$highest = 0;
						$highestDate = "";
						$lowest = PHP_FLOAT_MAX;
						$lowestDate = "";
						$totalPrices = 0;
						$d = sizeof($product->priceHistory);
						foreach ($product->priceHistory as $price) {
						    if($d <= 31) {
							    $xArr[] = "\"" . $price->x . "\"";
							    $yArr[] = $price->y;
						    }

							if ($lowest >= $price->y) {
								$lowest = $price->y;
								$lowestDate = strtotime($price->x);
							}
							if ($highest <= $price->y) {
								$highest = $price->y;
								$highestDate = strtotime($price->x);
							}

							$totalPrices += $price->y;

							$d--;
						}
						$avgPrice = round($totalPrices / sizeof($product->priceHistory), 2);
						?>
                        <tr>
                            <!--<td class="column1"><?php echo $product->id; ?></td>-->
                            <td class="column2">
                                <img style="width:64px;" src="<?php echo $product->image; ?>"/>
                            </td>
                            <td class="column3"><?php echo wordwrap($product->name, 20, "<br/>"); ?></td>
                            <td class="column4">
	                            <span<?php if (isset($product->oldPrice) && $product->oldPrice - $product->price != 0) { ?> style="color: #FF0000;"<?php } ?>>
                                    <?php echo numfmt_format_currency($fmt, $product->price, "EUR"); ?>
                                </span>
								<?php
								if ($product->price > $lowest)
									echo "<i class=\"fa fa-chevron-circle-up\" style='color:#FF0000;' aria-hidden=\"true\" title=\"+" . numfmt_format_currency($fmt, $product->price - $lowest, "EUR") . ", von " . numfmt_format_currency($fmt, $lowest, "EUR") . " " . timeAgo($lowestDate) . "\"></i>";
                                elseif ($product->price == $lowest && $product->price - $avgPrice <= -1.0)
									echo "<i class=\"fa fa-chevron-circle-down\" style=\"color:#00FF00;\" aria-hidden=\"true\" title=\"" . numfmt_format_currency($fmt, round($product->price - $avgPrice, 2), "EUR") . "\"></i>";
								else
									echo "<i class=\"fa fa-minus-circle\" aria-hidden=\"true\"></i>";
								?>
                            </td>
                            <td class="column5">
                                <canvas style="margin-top: 15px;" id="myChart<?php echo $product->id . $i; ?>" width="400" height="200"></canvas>
                                <br/>
                                <span style="">
                                    Tief: <?php echo numfmt_format_currency($fmt, $lowest, "EUR"); ?> (<?php echo timeAgo($lowestDate); ?>)
                                </span>
                                <br/>
                                <span style="">
                                    Höchst: <?php echo numfmt_format_currency($fmt, $highest, "EUR"); ?> (<?php echo timeAgo($highestDate); ?>)
                                </span>
                                <script>
                                    var ctx = document.getElementById('myChart<?php echo $product->id . $i; ?>').getContext('2d');
                                    var myChart = new Chart(ctx, {
                                        type: 'line',
                                        options: {
                                            legend: {
                                                display: false,
                                            },
                                            scales: {
                                                yAxes: [{
                                                    display: true,
                                                    ticks: {
                                                        steps: 10,
                                                        stepValue: 10,
                                                        suggestedMin: <?php echo $lowest - 20; ?>,
                                                        suggestedMax: <?php echo ($highest != 0) ? $highest + 20 : 100; ?>
                                                    }
                                                }]
                                            }
                                        },
                                        data: {
                                            labels: [
												<?php
												echo join(",", $xArr);
												?>
                                            ],
                                            datasets: [
                                                {
                                                    data: [
														<?php
														echo join(",", $yArr);
														?>
                                                    ],
                                                    label: "Price",
                                                    borderColor: "#3e95cd",
                                                    fill: false
                                                },
                                                {
                                                    data: [
														<?php
														echo $lowest . ",";
														for ($y = 1; $y < sizeof($xArr) - 1; $y++) {
															echo "NaN";
															if ($y < sizeof($xArr))
																echo ",";
														}
														echo $lowest;
														?>
                                                    ],
                                                    spanGaps: true,
                                                    label: "Niedrigster Preis",
                                                    borderColor: "#00FF00",
                                                    fill: false
                                                },
                                                {
                                                    data: [
														<?php
														echo $highest . ",";
														for ($y = 1; $y < sizeof($xArr) - 1; $y++) {
															echo "NaN";
															if ($y < sizeof($xArr))
																echo ",";
														}
														echo $highest;
														?>
                                                    ],
                                                    spanGaps: true,
                                                    label: "Höchster Preis",
                                                    borderColor: "#FF0000",
                                                    fill: false
                                                },
                                            ]
                                        }
                                    });
                                </script>
                            </td>
                            <td class="column6"><?php echo date("d.m.Y", $product->date); ?></td>
                            <td class="column7"><?php echo date("d.m.Y H:i:s", $product->updated); ?></td>
                            <td class="column8">
								<?php
								if (sizeof($product->shopLinks) > 0) {
									?>
                                    <a href="https://www.idealo.de<?php echo $product->shopLinks[0]; ?>"
                                       target="_blank">
                                        <img src="<?php echo $product->shopImages[0]; ?>"/>
                                    </a>
                                    <br/>
                                    <a href="https://www.idealo.de/preisvergleich/OffersOfProduct/<?php echo $product->id; ?>"
                                       target="_blank">
                                        Zu idealo
                                    </a>
								<?php } elseif (sizeof($product->shopImages > 0)) { ?>
                                    <a href="https://www.idealo.de/preisvergleich/OffersOfProduct/<?php echo $product->id; ?>"
                                       target="_blank">
                                        <img src="<?php echo $product->shopImages[0]; ?>"/>
                                    </a>
								<?php } else {
									?>
                                    <a href="https://www.idealo.de/preisvergleich/OffersOfProduct/<?php echo $product->id; ?>"
                                       target="_blank">
                                        Kaufen
                                    </a>
									<?php
								} ?>
                            </td>
                        </tr>
						<?php
						$i++;
						$totalPrice += $product->price;
					}
					?>
                    <tr>
                        <!--<th class="column1"></th>-->
                        <th class="column2"></th>
                        <th class="column3"></th>
                        <th class="column4">
                            Netto: <?php echo round($totalPrice - (($totalPrice / 100) * 19), 2); ?>€
                        </th>
                        <th class="column5"></th>
                        <th class="column6"></th>
                        <th class="column7"></th>
                        <th class="column8"></th>
                    </tr>
                    <tr>
                        <!--<th class="column1"></th>-->
                        <th class="column2"></th>
                        <th class="column3"></th>
                        <th class="column4">
                            Brutto: <?php echo $totalPrice; ?>€
                        </th>
                        <th class="column5"></th>
                        <th class="column6"></th>
                        <th class="column7"></th>
                        <th class="column8"></th>
                    </tr>
                    </tbody>
                    <thead>
                    <tr class="table100-head">
                        <!--<th class="column1">Produkt ID</th>-->
                        <th class="column2">Bild</th>
                        <th class="column3">Name</th>
                        <th class="column4">Preis</th>
                        <th class="column5">Preiskurve</th>
                        <th class="column6">Preisdatum</th>
                        <th class="column7">Aktualisiert</th>
                        <th class="column8">Anbieter</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>


<!--===============================================================================================-->
<script src="assets/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
<script src="assets/vendor/bootstrap/js/popper.js"></script>
<script src="assets/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
<script src="assets/vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
<script src="assets/js/main.js"></script>

</body>
</html>