<!DOCTYPE html>

<!--  PHP Test v1.4 (c) Copyright MDGroup Ltd 2019-2020

You have been provided with this file - a single page javascript/html application.
This page requires an API to function - you are to develop this API.
Your work will be judged on accuracy of execution, maintainability, and general understanding of API's, OOP and MVC.
Do NOT adapt an existing piece of work and leave extraneous code in your submitted files.
Your API will read and write JSON formatted data
The API will support 3 end points:
/api/exchange/100/USD/EUR - this call will convert 100 usd into euros, and return the amount
/api/exchange/info - this call will return a basic text string
/api/cache/clear - this call will clear the exchange rates cache
There is no authentication required for the API - it is deemed to be publicly available
Any invalid urls submitted to the API must return: {error:1,msg:"invalid request"}
Your API will support the following currencies: CAD, JPY, USD, GBP, EUR, RUB, HKD, CHF
If an invalid currency code is submitted, the API must return: {error:1,msg:"currency code XXX not supported"}
A successful call to the exchange rate endpoint must return: { error:0,amount:1.23,fromCache:1}
The amount must be rounded to 2 decimal places
The fromCache value will be 0 if the exchange rates were NOT in the cache (or expired), and 1 if the cached data was used
A successful call to the /api/exchange/info endpoint will return {error:0,msg:"API written by <your name>"}
A successful call to the /api/cache/clear endpoint will clear the cached data and return {error:0,msg:"OK"}
You will use the following API to obtain rates information: https://api.exchangeratesapi.io/latest
You must create a cache table to hold the results from an API fetch.
You must cache the FROM currency code, the TO currency code, and the exchange rate multipler between them, one currency pair per record
The data will be cached for a period determined by a variable in your code (initially 2 hours)
The 2 hours time limit should be easily configurable.
Your cache data must be stored in a MySQL or MariaDB database.
Your code should use SQL to manipulate the MySQL database directly: specifically, do not use a framework's (e.g. Laravel's) caching system etc.
The queries to MySQL should be designed to be as efficient as possible, minimising reads and writes.
Do NOT cache more currencies than required.
Only cache the currency being requested, not all currencies.
Your code must be object orientated.
Your code must use a PHP Framework. If not, then it must still follow a Model/View/Controller Structure

Please explain how your code should be hosted, or whether it can run under the php -S inbuilt server.
You may modify the api_url variable in the code below to call the correct IP/port of your API.
Note: the example JSON strings above are not necessarily valid format. Your script must output only valid JSON format strings.
Return your project code to us as either a zip file or in a public project hosted on Github or BitBucket.

For Bonus points:
1) The cache should clear itself automatically, periodically, of expired exchange rate pairs (no need for a separate program/process/cronjob)
2) If possible, if you have already cached say EUR > USD, then you should not cache USD > EUR but re-use the EUR>USD value in reverse (1/x)
-->

<html>
<head>
    <script>
        var api_url = 'http://localhost:8000';	// <---- modify this to point to your codes
    </script>

    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>

    <style type='text/css'>
        * { font: 13px arial, sans-serif; }
        body { color: white; background-color: #ddd; }
        h1 { font: bold 20px arial; margin: 0 0 10px; }
        .col { display: inline-block; width:100px; float:left; padding:5px 0; }
        .col2 { display: inline-block; float:left; padding:5px 0; }
        div.newrow { clear: both; line-height: 1.8em; }
        button { display:inline-block; margin-right:30px; padding:6px 10px; border-radius:5px; background-color:#eee; }
        button.small { padding:3px 5px; width:auto; font-size:11px; }
        select { height: 1.8em; }
        input { padding: 3px; border-radius:5px; }
        div.readonly { display:inline-block; border:1px solid white; width:100px; border-radius:5px; padding:3px; line-height:1.5em; }
        #outline { position: absolute; top: 50px; left:50px; width:350px; padding:40px; background-color:#056; border:4px solid #fff; border-radius:12px; }
    </style>
    <title>MDGroup PHP Test</title>
</head>
<body>
<div id="outline">
    <h1>Simple API Test</h1>

    <div class='col newrow'>Amount</div>
    <div class='col'><input type='text' id='amount'></div>

    <div class='col newrow'>From Currency</div>
    <div class='col'>
        <select id='from_c'>
            <option value='CAD'>CAD - Canadian Dollar</option>
            <option value='CHF'>CHF - Swiss Dollar</option>
            <option value='EUR'>EUR - Euro</option>
            <option value='GBP'>GBP - British Pound</option>
            <option value='HKD'>HKD - Hong Kong Dollar</option>
            <option value='JPY'>JPY - Japanese Yen</option>
            <option value='RUB'>RUB - Russian Ruble1</option>
            <option value='THB'>THB - Thai Bhat</option>
            <option value='USD'>USD - American Dollar</option>
        </select>
    </div>

    <div class='col newrow'>To Currency</div>
    <div class='col'>
        <select id='to_c'>
            <option value='CAD'>CAD - Canadian Dollar</option>
            <option value='CHF'>CHF - Swiss Dollar</option>
            <option value='EUR'>EUR - Euro</option>
            <option value='GBP'>GBP - British Pound</option>
            <option value='HKD'>HKD - Hong Kong Dollar</option>
            <option value='JPY'>JPY - Japanese Yen</option>
            <option value='RUB'>RUB - Russian Ruble</option>
            <option value='THB'>THB - Thai Bhat</option>
            <option value='USD'>USD - American Dollar</option>
        </select>
    </div>

    <div class='col newrow'>Result</div>
    <div class="col">
        <div class='readonly' id='result'>&nbsp;</div>
    </div>
    <div class='col newrow'>&nbsp;</div>
    <div class="col2">
        <button id='calc'>Calculate</button>
    </div>

    <div class='col newrow'>&nbsp;</div>
    <div class="col2">
        <button class="small" id='about'>About</button>
        <button class="small" id='clear'>Clear&nbsp;Cache</button>
    </div>
</div>

<script>
    $( document ).ready(function() {
        $('#calc').click(function() { calc(); });
        $('#about').click(function() { about(); });
        $('#clear').click(function() { clear(); });
    });


    function calc()
    {
        var amt = $('#amount').val();
        var from_c = $('#from_c').val();
        var to_c = $('#to_c').val();

        var url = api_url + "/api/exchange/"+amt+"/"+from_c+"/"+to_c;
        console.log('SEND:',url)
        $.ajax({
            type: 'GET',
            url: url,
            success: function (response) {
                console.log('RECV:',response);
                resp = JSON.parse(JSON.stringify(response));
                if (resp.error == 1) {
                    alert('ERROR: ' +resp.msg);
                } else {
                    $("#result").html(resp.amount);
                }
            }
        });
    }

    function about()
    {
        var url = api_url + "/api/exchange/info";
        console.log('SEND:',url);
        $.ajax({
            type: 'GET',
            url: url,
            success: function (response) {
                console.log('RECV:',response);
                resp = JSON.parse(JSON.stringify(response));
                if (resp.error == 1) {
                    alert('ERROR: ' +resp.msg);
                } else {
                    alert(resp.msg);
                }
            }
        });
    }


    function clear()
    {
        var url = api_url + "/api/cache/clear";
        console.log('SEND:',url)
        $.ajax({
            type: 'GET',
            url: url,
            success: function (response) {
                console.log('RECV:',response);
                resp = JSON.parse(JSON.stringify(response));
                if (resp.error == 1) {
                    alert('ERROR: ' +resp.msg);
                } else {
                    alert(resp.msg);
                }
            }
        });
    }

</script>

</body>
</html>
