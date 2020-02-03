<?php
function getProductData(int $productId)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://www.idealo.de/preisvergleich/OffersOfProduct/" . $productId);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36");
	$output = curl_exec($ch);
	file_put_contents("tests/".$productId.".html", $output);

	curl_close($ch);
	if (!preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $output_array))
		die("Error getting product data");

	if (!preg_match_all('/&#034;shop_name&#034;:.*?&#034;(.*?)&#034;,/s', $output, $output_array2))
		die("error getting shopinfo");

	if(!preg_match_all('/<img.*?class="productOffers-listItemOfferLogoShop".*?data-shop-logo="(.*?)".*?>/sm', $output, $output_array3))
		die("error getting shopimages");
	if(!preg_match_all('/<a.*?class="productOffers-listItemOfferLogoLink".*?href="(.*?)"/sm', $output, $output_array4))
		die("error getting shopimages");

	$result = json_decode($output_array[1][0]);
	$result->shops = $output_array2[1];
	$result->shopImages = $output_array3[1];
	$result->shopLinks = $output_array4[1];
	return $result;
}

function getProductPrices(int $productId)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://www.idealo.de/offerpage/pricechart/api/" . $productId . "?period=P3M");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36");
	$output = curl_exec($ch);
	curl_close($ch);

	$json = json_decode($output, TRUE);

	$keys = array_keys($json["data"]);

	$lastPrice = $json["data"][end($keys)];

	$priceData = new stdClass();
	$priceData->Date = strtotime($lastPrice["x"]);
	$priceData->Price = $lastPrice["y"];
	$priceData->PriceHistory = $json["data"];

	return $priceData;
}

$productIds = [
	6461685,
	5701206,
	6436334,
	6431557,
	4029152,
	4029152,
	4029152,
	4029152,
	5480973,
	6143332,
	5075545,
	5654702,
	6647852,
];

$oldProductData = [];
$priceData = json_decode(file_get_contents("pricedata.json"));
if(isset($priceData) && sizeof($priceData) > 0)
foreach ($priceData as $product) {
	$oldProductData[$product->id] = $product;
}

$products = [];
foreach ($productIds as $productId) {
	if (array_key_exists($productId, $oldProductData)) {
		$product = $oldProductData[$productId];

		$ppData = getProductPrices($productId);

		$product->date = $ppData->Date;
		$product->updated = time();

		$product->oldPrice = $product->price;

		$product->price = $ppData->Price;
		$product->priceHistory = $ppData->PriceHistory;
	} else {
		$productData = getProductData($productId);

		$product = new stdClass();
		$product->id = $productId;
		$product->name = $productData->name;
		$product->image = $productData->image;
		$product->price = $productData->offers->lowPrice;
		$product->shops = $productData->shops;
		$product->shopImages = $productData->shopImages;
		$product->shopLinks = $productData->shopLinks;
		$product->updated = time();

		$ppData = getProductPrices($productId);
		$product->date = $ppData->Date;
		$product->priceHistory = $ppData->PriceHistory;
	}

	$products[] = $product;
}

file_put_contents("backups/" . date("d_m_Y_H_i_s") . "_pricedata.json.bak", json_encode($priceData));
file_put_contents("pricedata.json", json_encode($products));
/*

$productId = 5041022;

$jFile = file_get_contents("test-pricechart.json");

$json = json_decode($jFile, TRUE);

$keys = array_keys($json["data"]);

$lastPrice = $json["data"][end($keys)];

$priceData = new stdClass();
$priceData->Date = date_parse($lastPrice["x"]);
$priceData->Price = $lastPrice["y"];

echo "<pre>";
var_dump($priceData);
var_dump($lastPrice);
echo "</pre>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.idealo.de/offerpage/fragment/internationalprices/products/" . $productId);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$output = curl_exec($ch);
curl_close($ch);

if (!preg_match('/<span class="oopMarginal-wrapperTitleSub">(.*)<\/span>/', $output, $output_array))
	die("ERROR");

$name = $output_array[1];

var_dump($name);


$productData = getProductData($productId);
echo "<pre>";
var_dump($productData);
echo "</pre>";

echo $productData->name;
echo $productData->image;
echo $productData->offers->lowPrice;

?>
<table style="width: 100%;">
    <thead>
        <tr>
            <th>Bild</th>
            <th style="width:40%;">Name</th>
            <th style="width:40%;">Preis</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <img src="<?php echo $productData->image; ?>" />
            </td>
            <td><?php echo $productData->name; ?></td>
            <td><?php echo $productData->offers->lowPrice; ?>€</td>
        </tr>
    </tbody>
</table>*/