<?php


//$db = new PDO("sqlsrv:Server=AXIMOS-SERVER\MSSQLSERVER_AXMO;Database=aximos", "Axis", "Axis1234");
$db = new PDO("sqlsrv:Server=WIN-QPJF4N0OD9L;Database=AximosOTS", "sa", "axis1234");
//$db = new PDO("sqlsrv:Server=sql5019.site4now.net;Database=DB_A33C8A_aximos", "DB_A33C8A_aximos_admin", "axis1234");

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function get_today_sales()
{
     global $db;
     //$result=array();
    try {
        $sql = $db->prepare('select sum(InvoiceTotal) as todaySales from tblInvoiceBasicInfo where CONVERT (DATE, CreatedDate)=? and InvoiceTotal>0');
        $sql->execute(array(date('Y-m-d')));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
       
   } catch (Exception $ex) {
       $result =  $ex->getMessage();
    }

    return $result;
}

function get_month_sales(){
     global $db;
     //$result=array();
    try {
       $this_month = date("m");
        $sql = $db->prepare('select sum(InvoiceTotal) from tblInvoiceBasicInfo where month(CreatedDate) =? and InvoiceTotal>0');
        $sql->execute(array($this_month));
        $result = $sql->fetchColumn();
       
   } catch (Exception $ex) {
       $result =  $ex->getMessage();
    }

    return $result;
}


function get_week_sales(){
      global $db;
     $this_sunday = date("Y-m-d H:i:s", strtotime('last sunday', strtotime('this week', time())));
    $this_sart = date ("Y-m-d H:i:s", strtotime ($this_sunday ."+6 days"));
    try {
        $sql = $db->prepare('select * from tblInvoiceBasicInfo where CreatedDate>=? and CreatedDate<=? and InvoiceTotal>0');
        $sql->execute(array($this_sunday,$this_sart));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function sales_invoice_today(){
      global $db;
     //$result=array();
    try {
        $sql = $db->prepare('select * from tblInvoiceBasicInfo where CONVERT (DATE, CreatedDate)=? and InvoiceTotal>0');
        $sql->execute(array(date('Y-m-d')));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
       
   } catch (Exception $ex) {
       $result =  $ex->getMessage();
    }

    return $result;
}


function get_year_sales(){
     global $db;
     //$result=array();
    try {
        $sql = $db->prepare('select sum(InvoiceTotal) as total from tblInvoiceBasicInfo where ?-CreatedDate<=365 and InvoiceTotal>0');
        $sql->execute(array(date('Y-m-d H:i:s')));
        $result = $sql->fetchColumn();
       
   } catch (Exceptiown $ex) {
       $result =  $ex->getMessage();
    }

    return $result;
}


function  this_week_dates(){
     global $db;
     $this_sunday = date("Y-m-d H:i:s", strtotime('last sunday', strtotime('this week', time())));
    $this_sart = date ("Y-m-d H:i:s", strtotime ($this_sunday ."+6 days"));
    try {
        $sql = $db->prepare('select distinct(CONVERT (DATE, CreatedDate)) as inv_dates from tblInvoiceBasicInfo where CreatedDate>=? and CreatedDate<=? and InvoiceTotal>0');
        $sql->execute(array($this_sunday,$this_sart));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function per_day_sales($date){
      global $db;

    try {
        $sql = $db->prepare('select sum(InvoiceTotal) as total from tblInvoiceBasicInfo where CONVERT(DATE, CreatedDate)=? and InvoiceTotal>0');
        $sql->execute(array($date));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result; 
}


function  last_week_sales(){
     global $db;
     $last_sunday = date("Y-m-d H:i:s", strtotime('last sunday', strtotime('last week', time())));
    $last_sart = date ("Y-m-d H:i:s", strtotime ($last_sunday ."+6 days"));
    try {
        $sql = $db->prepare('select distinct(CONVERT (DATE, CreatedDate)) as inv_dates from tblInvoiceBasicInfo where CreatedDate>=? and CreatedDate<=? and InvoiceTotal>0');
        $sql->execute(array($last_sunday,$last_sart));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}



function sales_by_customer($cust_name,$date_start,$date_end){
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select distinct([InvoiceNum]) from tblInvoices where CustomerName = ?  and CreatedDate between ? and ? and InvoiceTotal>0');
        $sql->execute(array($cust_name,$date_start,$date_end));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//print_r(sales_by_customer("Bhadella","2016-08-01","2016-08-16"));

function sales_by_salesman($salesman,$date_start,$date_end){
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select distinct([InvoiceNum]) from tblInvoices where InvoiceTotal>0 and SalesmanName = ?  and CreatedDate between ? and ?');
        $sql->execute(array($salesman,$date_start,$date_end));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//sales by route
function sales_by_route($routeName,$date_start,$date_end){
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select distinct([InvoiceNum]) from tblInvoices where CustomerName in (select CustomerName from tblCustomers where RouteName=?)  and CreatedDate between ? and ? and InvoiceTotal>0');
        $sql->execute(array($routeName,$date_start,$date_end));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//customer orders by customer
function Order_by_customer($customer_name,$date_start,$date_end){
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblCustomerOrders where CustomerName = ?  and CreatedDate between ? and ?');
        $sql->execute(array($customer_name,$date_start,$date_end));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result; 
}

//orders by Salesman

function Orders_by_salesman($salesman,$date_start,$date_end){
    
    global $db;
    
    try {
        $sql = $db->prepare('select * from tblCustomerOrders where SalesmanName = ? and CreatedDate between ? and ?');
        $sql->execute(array($salesman,$date_start,$date_end));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result; 
}

function Orders_by_Route($routeName,$date_start,$date_end){
   global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblCustomerOrders where SalesmanName in (select Username from tblUsers where RouteName=?) and CreatedDate between ? and ?');
        $sql->execute(array($routeName,$date_start,$date_end));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}


function Orders_by_status($order_status,$date_start,$date_end){
    
    global $db;
    
    try {
        $sql = $db->prepare('select * from tblCustomerOrders where OrderStatus = ? and CreatedDate between ? and ?');
        $sql->execute(array($order_status,$date_start,$date_end));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result; 
}


function get_channel_invoices($channel,$date_from,$date_to){
      global $db;
    
    try {
        $sql = $db->prepare('select * from tblInvoiceBasicInfo where CustomerName in (select CustomerName from tblCustomers where CustomerType = ?) and CreatedDate between ? and ? and InvoiceTotal>0');
        $sql->execute(array($channel,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result; 
}
function get_distinct_customer(){
     global $db;
    
    try {
        $sql = $db->prepare('select distinct(CustomerName) from tblInvoiceBasicInfo where  InvoiceTotal>0');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}


function get_total_sales($customer_name,$date_from,$date_to){ //top ten SKUs
     global $db;
   
    try {
        $sql = $db->prepare('select sum(InvoiceTotal) as total, count(InvoiceTotal) as counter from tblInvoiceBasicInfo WHERE CustomerName=? and CreatedDate between ? and ?  and InvoiceTotal>0');
        $sql->execute(array($customer_name,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
        $inv_total = $result[0]['total'];
        $col_counter = $result[0]['counter'];
        $inv_combination = array("cust_name"=>$customer_name,"totals"=>$inv_total,"col_count"=>$col_counter);
    } catch (Exception $ex) {
        $inv_combination[] = $ex->getMessage();
    }

    return $inv_combination; 
    
}



function get_accType_sales($acc_type,$date_from,$date_to){
      global $db;
    
    try {
        $sql = $db->prepare('select * from tblInvoiceBasicInfo where CustomerName in (select CustomerName from tblCustomers where PaymentMethod = ?) and CreatedDate between ? and ?  and InvoiceTotal>0');
        $sql->execute(array($acc_type,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result; 
}

function in_sales($Pname,$date_from,$date_to){
        global $db;
     //$result=array();
    try {
        $sql = $db->prepare('select sum(ProductQty) as Sales from tblInvoices where ProductName=? and CreatedDate between ? and ?  and InvoiceTotal>0');
        $sql->execute(array($Pname,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
       
   } catch (Exception $ex) {
       $result =  $ex->getMessage();
    }

    return $result;
}

function inload_qty($name,$date_from,$date_to){
      global $db;
     //$result=array();
    try {
        $sql = $db->prepare('select sum(InLoad) as inload from tblRouteSheetTruckLoadItems where ProductName=? and CreatedDate between ? and ?');
        $sql->execute(array($name,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
       
   } catch (Exception $ex) {
       $result =  $ex->getMessage();
    }

    return $result;
}

function outload_qty($name,$date_from,$date_to){
      global $db;
     //$result=array();
    try {
        $sql = $db->prepare('select sum(Out) as out from tblRouteSheetTruckLoadItems where ProductName=? and CreatedDate between ? and ?');
        $sql->execute(array($name,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
       
   } catch (Exception $ex) {
       $result =  $ex->getMessage();
    }

    return $result;
}

function get_distinct_prod(){
     global $db;
    
    try {
        $sql = $db->prepare('select distinct(ProductName) from tblInvoices where InvoiceTotal>0');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

//products by customer
function get_distinct_pro_cust($cust_name){
     global $db;
    
    try {
        $sql = $db->prepare('select distinct(ProductName) from tblInvoices where CustomerName=?  and InvoiceTotal>0');
        $sql->execute(array($cust_name));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

//products by customer
function get_distinct_pro_salsman($slsman_nam){
     global $db;
    
    try {
        $sql = $db->prepare('select distinct(ProductName) from tblInvoices where SalesmanName=?  and InvoiceTotal>0');
        $sql->execute(array($slsman_nam));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

function get_product_total_sales($prod_name,$date_from,$date_to){ //top ten SKUs
     global $db;
   
    try {
        $sql = $db->prepare('select sum(ProductQty) as total_qty, count(ProductQty) as counter, sum(ProductTotal) as prod_total from tblInvoices WHERE ProductName=? and CreatedDate between ? and ?  and InvoiceTotal>0');
        $sql->execute(array($prod_name,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
        $ttl_qty = $result[0]['total_qty'];
        $ttl_sold = $result[0]['prod_total'];
        $counter = $result[0]['counter'];
        $inv_combination = array("prod_name"=>$prod_name,"total_qty"=>$ttl_qty,"total_sold"=>$ttl_sold,"#_of_times_sold"=>$counter);
    } catch (Exception $ex) {
        $inv_combination[] = $ex->getMessage();
    }

    return $inv_combination; 
    
}


function get_product_total_sales_by_cust($prod_name,$cust_name,$date_from,$date_to){ //top ten SKUs
     global $db;
   
    try {
        $sql = $db->prepare('select sum(ProductQty) as total_qty, count(ProductQty) as counter, sum(ProductTotal) as prod_total from tblInvoices WHERE ProductName=? and CustomerName=? and CreatedDate between ? and ?  and InvoiceTotal>0');
        $sql->execute(array($prod_name,$cust_name,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
        $ttl_qty = $result[0]['total_qty'];
        $ttl_sold = $result[0]['prod_total'];
        $counter = $result[0]['counter'];
        $inv_combination = array("prod_name"=>$prod_name,"total_qty"=>$ttl_qty,"total_sold"=>$ttl_sold,"#_of_times_sold"=>$counter);
    } catch (Exception $ex) {
        $inv_combination[] = $ex->getMessage();
    }

    return $inv_combination; 
    
}

function get_product_total_sales_by_salsmn($prod_name,$slsmn,$date_from,$date_to){ //top ten SKUs
     global $db;
   
    try {
        $sql = $db->prepare('select sum(ProductQty) as total_qty, count(ProductQty) as counter, sum(ProductTotal) as prod_total from tblInvoices WHERE ProductName=? and SalesmanName=? and CreatedDate between ? and ?  and InvoiceTotal>0');
        $sql->execute(array($prod_name,$slsmn,$date_from,$date_to));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
        $ttl_qty = $result[0]['total_qty'];
        $ttl_sold = $result[0]['prod_total'];
        $counter = $result[0]['counter'];
        $inv_combination = array("prod_name"=>$prod_name,"total_qty"=>$ttl_qty,"total_sold"=>$ttl_sold,"#_of_times_sold"=>$counter);
    } catch (Exception $ex) {
        $inv_combination[] = $ex->getMessage();
    }

    return $inv_combination; 
    
}

function get_distinct_currency(){
     global $db;
    
    try {
        $sql = $db->prepare("select distinct(Currency) from tblInvoiceTender where Currency!='Account'");
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

function get_currency_details($currency,$date_from,$date_to){
     global $db;
    
    try {
        $sql = $db->prepare("select sum(AmountTendered) as forex,sum(BaseCurrencyValue) as basecur from tblInvoiceTender where Currency=? and DateSet between ? and ?");
        $sql->execute(array($currency,$date_from,$date_to)); 
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

function get_today_rate($curr){
     global $db;
    
    try {
        $sql = $db->prepare("select USDExchangeRate from tblCurrency where (Currency=? or CurrencyCode=?)");
        $sql->execute(array($curr,$curr)); 
        $result = $sql->fetchColumn();
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

function get_user_sales($user_id){
        global $db;
    
    try {
        $sql = $db->prepare("select sum(InvoiceTotal) as total from tblInvoiceBasicInfo where CreatedBy=?  and InvoiceTotal>0");
        $sql->execute(array($user_id)); 
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

function get_user_orders($user_id){
        global $db;
    
    try {
        $sql = $db->prepare("select sum(OrderTotal) as total from tblCustomerOrders where CreatedBy=?");
        $sql->execute(array($user_id)); 
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

function get_user_created($user_id){
        global $db;
    
    try {
        $sql = $db->prepare("select * from tblUsers where CreatedBy=?");
        $sql->execute(array($user_id)); 
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}

function get_salesman_routesheets($user_id){
     global $db;
    
    try {
        $sql = $db->prepare("select * from tblDailyRouteSheetHeader where (SalesManID=? or SalesMan = ?)");
        $sql->execute(array($user_id,$user_id)); 
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;  
}



function get_total_loadqty($route_sht_num){
     global $db;
    
    try {
        $sql = $db->prepare("select sum(Out) as outload from tblRouteSheetTruckLoadItems where RouteSheetNumber=?");
        $sql->execute(array($route_sht_num)); 
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;   
}

function show_salesman_inv($user_name){
     global $db;
    try {

        $sql = $db->prepare('select * from tblInvoiceBasicInfo where SalesManName=?  and InvoiceTotal>0 order by CreatedDate desc');
        $sql->execute(array($user_name));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function AllTimeSalesBySalesman(){
     global $db;
    try {

        $sql = $db->prepare('select sum(InvoiceTotal) as Total,SalesManName from tblInvoiceBasicInfo where InvoiceTotal>0 group by SalesManName');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}
function SalesByCustAllTime(){
     global $db;
    try {

        $sql = $db->prepare('select sum(InvoiceTotal) as Total,CustomerName from tblInvoiceBasicInfo where InvoiceTotal>0 group by CustomerName');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function SalesByRouteAllTime(){
     global $db;
    try {

        $sql = $db->prepare('SELECT [RouteName],sum([InvoiceTotal]) as MySum  FROM ViewSalesByRoute group by RouteName');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}


function TopTenCust(){
     global $db;
    try {

        $sql = $db->prepare('SELECT TOP 10 [Total],[CustomerName] FROM ViewCustSales order by Total desc');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function TopFiveCust(){
     global $db;
    try {

        $sql = $db->prepare('SELECT TOP 5 [Total],[CustomerName] FROM ViewCustSales order by Total desc');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function TopTenSalesman(){
     global $db;
    try {

        $sql = $db->prepare('SELECT TOP 10 [Total],[SalesManName] FROM ViewSalesBySalesMan order by Total desc');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function TopFiveSalesman(){
     global $db;
    try {

        $sql = $db->prepare('SELECT TOP 5 [Total],[SalesManName] FROM ViewSalesBySalesMan order by Total desc');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function TopTenSkus(){
     global $db;
    try {

        $sql = $db->prepare('SELECT TOP 10 [ProductName],[ProdLineTot] FROM ViewSalesBySku order by ProdLineTot desc ');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function TopFiveSkus(){
     global $db;
    try {

        $sql = $db->prepare('SELECT TOP 5 [ProductName],[ProdLineTot] FROM ViewSalesBySku order by ProdLineTot desc ');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

//
function get_invoice_coord(){
  global $db;
    try {

        $sql = $db->prepare('select distinct(InvoiceNum),GPSLatitude,GPSLongitude,InvoiceTotal,CustomerName from tblInvoices');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;   
}

function show_geo_customers() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select CustomerName,LocationGPSLatitude,LocationGPSLongitude from tblCustomers where LocationGPSLatitude!=? ');
        $sql->execute(array("NULL"));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}


function show_geo_payments() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from [tblRecievePayments] where Latitude!=?');
        $sql->execute(array("NULL"));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetClientVisits(){
     global $db;
  
    try {
        $sql = $db->prepare("select * from tblClientVisits");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function SuggestionBoxes(){
     global $db;
  
    try {
        $sql = $db->prepare("select * from tblSuggestionBox");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}


function GetFirstInv($RouteSheetNumber){
     global $db;
  
    try {
        $sql = $db->prepare("select top 1 * from tblInvoices where InvoiceNum in (Select InvoiceNum from tblInvoiceBasicInfo where LoadSheetName=?) order by InvoiceID asc");
        $sql->execute(array($RouteSheetNumber));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) 
        {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function  getFirstInvCoord($RouteSheetNumber){
     global $db;
  
    try {
        $gpsLat = "0.0";
        $gpsLon = "0.0";
        $sql = $db->prepare("select top 1 * from tblInvoices where InvoiceNum in (Select InvoiceNum from tblInvoiceBasicInfo where LoadSheetName=?) and  (GPSLatitude!=? and GPSLongitude!=?)  order by InvoiceID asc");
        $sql->execute(array($RouteSheetNumber,$gpsLat,$gpsLon));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) 
        {
        $result['status'] = $ex->getMessage();
    }
    return $result;
    
}

function GetLastInvCoord($Loadsheet){
     global $db;
  
    try {
        $gpsLat = "0.0";
        $gpsLon = "0.0";
        $sql = $db->prepare("select top 1 * from tblInvoices where InvoiceNum in (Select InvoiceNum from tblInvoiceBasicInfo where LoadSheetName=?)  and  (GPSLatitude!=? and GPSLongitude!=?) order by InvoiceID desc");
        $sql->execute(array($Loadsheet,$gpsLat,$gpsLon));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}


function GetWayPointsInvoices($Loadsheet,$InvToRemove){
     global $db;
  
    try {
        //first block
        $OutputData = array();
         $sql1 = $db->prepare("select distinct(InvoiceNum) from tblInvoices where InvoiceNum in (Select InvoiceNum from tblInvoiceBasicInfo where LoadSheetName=?)  and InvoiceNum!=? ");
        $sql1->execute(array($Loadsheet,$InvToRemove));
        $result1 = $sql1->fetchAll(PDO::FETCH_ASSOC);
        if(sizeof($result1)>0)
        {
            foreach($result1 as $dt){
                
                $InvNum = $dt["InvoiceNum"];
             $sql = $db->prepare("select InvoiceTotal,GPSLatitude,GPSLongitude from tblInvoices where InvoiceNum = ?");
        $sql->execute(array($InvNum));
        $result2 = $sql->fetchAll(PDO::FETCH_ASSOC);
        array_push($OutputData, $result2[0]);
        $result= $OutputData;
            }
        }
        
        
       
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}


/******************************GET GEO LOCATIONAL DATA(****************
 * 1. Track route plan path based on plan - SHOW MAP AND RELATED INFOR - incl sales in that route
 * 2. 
 */


function GetRouteCoordinates($RouteReference){
    global $db;
  
    try {
       
        $sql = $db->prepare("select * from luRouteCenters where RouteReference =? order by Sequence asc");
        $sql->execute(array($RouteReference));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function GetRouteDetails($RouteCode) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblRoutes where (RouteCode=? or RouteName=?)');
        $sql->execute(array($RouteCode,$RouteCode));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function SalesByRouteName($routeName){
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblInvoiceBasicInfo where CustomerName in (select CustomerName from tblCustomers where RouteName=?) and InvoiceTotal>0');
        $sql->execute(array($routeName));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetLoadCordinatesSummary($LoadSheet){ //tblRouteCoordinates
     global $db;
    try {

        $sql = $db->prepare('SELECT * from tblRouteCoordinates where RouteSheetName=?');
        $sql->execute(array($LoadSheet));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

Function GetRouteCustomers($LoadSheet)
{ //
    global $db;
    try {

        $sql = $db->prepare('SELECT * from [tblCustomers] where RouteName=(select RouteName from tblDailyRouteSheetHeader where SheetNumber=?)');
        $sql->execute(array($LoadSheet));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result; 
}

function GetOutletStockHolding($Customer,$DateFrom,$DateTo){
   global $db;
    try {

        $sql = $db->prepare('SELECT * from [tblOutletStockHolding] where CustomerName =? and DateCreated between ? and ?');
        $sql->execute(array($Customer,$DateFrom,$DateTo));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}

function GetCurrentStockHolding($Customer){
    global $db;
    try {

        $sql = $db->prepare('SELECT *  FROM [tblOutletStockHolding]  WHERE VisitID IN (SELECT MAX(VisitID) FROM [tblOutletStockHolding] where CustomerName = ? GROUP BY ProductName) ');
        $sql->execute(array($Customer));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}

function GetCompetitorInfor(){
     global $db;
    try {

        $sql = $db->prepare('SELECT *  FROM [TblCompetitorInfo]');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}

function getDistinctLoadGivenDate($Salesman,$DateFrom,$DateTo){
     global $db;
    try {

        $sql = $db->prepare('SELECT distinct(LoadSheetName) as ldsheet  FROM [vwSalesByLoadSheetBySalesman] where SalesManName = ? and CreatedDate between ? and ?');
        $sql->execute(array($Salesman,$DateFrom,$DateTo));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}

function getDistinctLoad($Salesman){
     global $db;
    try {

        $sql = $db->prepare('SELECT distinct(LoadSheetName) as ldsheet  FROM [vwSalesByLoadSheetBySalesman] where SalesManName = ? ');
        $sql->execute(array($Salesman));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
} 

Function getDistinctLoadCustomers($Load){
    global $db;
    try {

        $sql = $db->prepare('SELECT distinct(CustomerName) as Cust  FROM [vwSalesByLoadSheetBySalesman] where LoadSheetName = ? ');
        $sql->execute(array($Load));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}

function getCustomerHitRate($Loadsheet){
     global $db;
    try {

        $sql = $db->prepare('SELECT *  FROM [vwSalesByLoadSheetBySalesman] where LoadSheetName = ? ');
        $sql->execute(array($Loadsheet));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
} 

function getNumOfTargetVisit($LoadSheet) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblCustomerVisitCustomers where LoadSheetNumber = ? ');
        $sql->execute(array($LoadSheet));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function numOfActualVisits($LoadSheet) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from [tblClientVisits] where Loadsheet# = ? ');
        $sql->execute(array($LoadSheet));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function GetSalesByLoadSheet($LoadsheetNumber)
{
    global $db;
      try {
        $sql = $db->prepare("select * from vwSalesByLoad where LoadSheetName=?");
        $sql->execute(array($LoadsheetNumber));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;  
}

function get_all_salesman() {
    global $db;
    //$result=array();
    try {
        //$sql = $db->prepare('select * from tblUsers where UserType like %Salesman%');
		$sql = $db->prepare('select * from tblUsers');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//get salesman revenue between dates
function GetSalesmanRevenue($Salesman,$DateFrom,$DateTo){
   global $db;
    try {

				  $sql = $db->prepare('
				                     SELECT 
				    sum([ClosingMileage]-[OpeningMileage]) As  Distance,
				    sum(InvoiceTotal) AS Total,
					SalesMan
					FROM  revenuePerKilometre
					where SalesMan = ? and InvoiceDate between ? and ?
					 GROUP BY SalesMan
						 ');   
		   
        $sql->execute(array($Salesman,$DateFrom,$DateTo));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}
         
//print_r(GetSalesmanRevenue('Mudiwa','2017-06-23 15:27:03.000','2017-06-23 15:27:03.000'));

//get salesman current revenue
function GetCurrentSalesmanRevenue($Customer){
    global $db;
    try {

        $sql = $db->prepare('
		            SELECT 
				    [SalesMan],
				    sum([ClosingMileage]-[OpeningMileage]) As  Distance,
				    sum(InvoiceTotal) AS Total
					FROM revenuePerKilometre 
					where SalesMan = ? 
					GROUP BY SalesMan
		        ');
        $sql->execute(array($Customer));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}
//print_r(GetCurrentSalesmanRevenue('Mudiwa'));

//get planned distance from route table using RouteName used by Salesman
function get_planned_distance($Salesman) {
    global $db;
    $status;
    try {

        $sql = $db->prepare('select * from revenuePerKilometre where SalesMan = ?');
        $sql->execute(array($Salesman));
        $result2 = $sql->fetchALL(PDO::FETCH_ASSOC);
		$routename = $result2[0]['RouteName'];
		$sql = $db->prepare('select TargetDistance from tblRoutes where RouteName = ?');
        $sql->execute(array($routename));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
		
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}
//print_r(get_planned_distance("Mudiwa"));

//Actual Versus Planned Targets Function

//Actual and planned for Salesman
function GetSalesmanPerfomanceTargets($dateTo,$datefrom, $salesman){
   global $db;
  $dateTO = floatval($dateTo);
  $datefrom = floatval($datefrom);  
    try {

				  $sql = $db->prepare("
				    SELECT 
				    Sum(isnull(cast(? as float),0.00)) As  Month1,
				    Sum(isnull(cast(? as float),0.00)) As  Month2,
					SalesMan
					FROM  salesmanTargets 
					where SalesMan = ?
					GROUP BY SalesMan");   
		   
        $sql->execute(array($dateTo,$datefrom,$salesman));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}
//print_r(GetSalesmanPerfomanceTargets(january,february,"Trayi"));


//Products Planned versus Target Functions
//get products
function get_all_products() {
    global $db;
    //$result=array();
    try {
        //$sql = $db->prepare('select * from tblUsers where UserType like %Salesman%');
		$sql = $db->prepare('select product from productsTargets');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//get product and respective perfomance target
function GetProductPerfomanceTargets($dateTo,$datefrom, $product){
   global $db;
  $dateTO = floatval($dateTo);
  $datefrom = floatval($datefrom);  
    try {

				  $sql = $db->prepare("
				    SELECT 
				    Sum(isnull(cast(? as float),0.00)) As  Month1,
				    Sum(isnull(cast(? as float),0.00)) As  Month2,
					product
					FROM  [aximos].[dbo].[productsTargets] 
					where product = ?
					GROUP BY product");   
		   
        $sql->execute(array($dateTo,$datefrom,$product));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}
//print_r(GetSalesmanPerfomanceTargets('january','february','TEST TWO TWO'));

//customer actual and planned 
//get products
function get_all_customers() {
    global $db;
    //$result=array();
    try {
        //$sql = $db->prepare('select * from tblUsers where UserType like %Salesman%');
		$sql = $db->prepare('select Customer from customerTargets');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}
//print_r(get_all_customers());



 
function GetCustomerPerfomanceTargets($dateTo,$datefrom, $customer){
   global $db;
  $dateTO = floatval($dateTo);
  $datefrom = floatval($datefrom);  
    try {

				  $sql = $db->prepare("
				    SELECT 
				    Sum($dateTo) As  Month1,
				    Sum($datefrom) As  Month2,
					Salesman
					FROM  [aximos].[dbo].[salesmanTargets] 
					where Salesman = ?
					GROUP BY Salesman");   
		   
        $sql->execute(array($customer));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;  
}

function DelBySalesman($SalesmanName,$InvoiceStatus,$InvoiceStatus2,$DateFunc,$DateFrom,$DateTo){
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("select distinct(InvoiceNum),InvoiceStatus,InvoiceTotal,SalesmanName,
                RouteSheetNumber,CustomerName,DateDelivered,CreatedDate,TimeIn,GPSLatitude,GPSLongitude
                from [tblDeliveries] where  (SalesmanName = ? or Deliverer = ?) AND (InvoiceStatus = ? or InvoiceStatus = ?)
                and $DateFunc between ? and ? ");
        $sql->execute(array($SalesmanName,$SalesmanName,$InvoiceStatus,$InvoiceStatus2, $DateFrom,$DateTo));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}



function DeliveriesBySalesmanByCustomer($SalesmanName,$CustomerName,$InvoiceStatus,$InvoiceStatus2,$DateFunc,$DateFrom,$DateTo){
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("select distinct(InvoiceNum),InvoiceStatus,InvoiceTotal,SalesmanName,RouteSheetNumber,CustomerName,DateDelivered,CreatedDate,TimeIn,GPSLatitude,GPSLongitude from [tblDeliveries] where  (SalesmanName = ? or Deliverer = ?) AND CustomerName = ? and (InvoiceStatus = ? or InvoiceStatus = ?) and  $DateFunc between ? and ? ");
        $sql->execute(array($SalesmanName,$SalesmanName,$CustomerName,$InvoiceStatus,$InvoiceStatus2,$DateFrom,$DateTo));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}










