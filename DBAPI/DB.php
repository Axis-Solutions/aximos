<?php

ob_start();
session_start();
/* * *********************This is the main database API********It handles all database connection functions ****** */

$db = new PDO("sqlsrv:Server=WIN-QPJF4N0OD9L;Database=AximosOTS", "sa", "axis1234");
//$db = new PDO("sqlsrv:Server=AXIMOS-SERVER\MSSQLSERVER_AXMO;Database=aximos", "Axis", "Axis1234");
//$db = new PDO("sqlsrv:Server=sql5019.site4now.net;Database=DB_A33C8A_aximos", "DB_A33C8A_aximos_admin", "axis1234");



$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, 1);

function redirect($url) {
    header("Location: $url");
}

function db_connect() {
    global $db;
    try {
        if ($db) {
            echo 'Connection established';
        } else {
            echo 'Failed';
        }
    } catch (PDOExcepion $ex) {
        echo $ex->getMessage();
    }
}

//set up[ company credentials by the super user

function create_company_details($companyName, $websiteURL, $CompanyAddr, $CompanyBPN, $companyVAT, $companyLogo, $user_acc) 
        {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblCompanyDetails (CompanyName,WebsiteURL,CompanyAddress,CompanyBPN,CompanyVATN,CompanyLogo,DateSet,SetBy) values(?,?,?,?,?,?,?,?)');
        $sql->execute(array($companyName, $websiteURL, $CompanyAddr, $CompanyBPN, $companyVAT, $companyLogo, date('Y-m-d H:i:s'), $user_acc));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function edit_company_details($companyName, $websiteURL, $CompanyAddr, $CompanyBPN, $companyVAT, $companyLogo) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblCompanyDetails set CompanyName=?,WebsiteURL=?,CompanyAddress=?,CompanyBPN=?,CompanyVATN=?,CompanyLogo=?,UpdatedDate=?,UpdatedBy=?');
        $sql->execute(array($companyName, $websiteURL, $CompanyAddr, $CompanyBPN, $companyVAT, $companyLogo, date('Y-m-d H:i:s'), $_SESSION['acc']));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function this_co_details() {

    global $db;
    try {

        $sql = $db->prepare(' select * from tblCompanyDetails');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//print_r(this_co_details());
//print_r(create_company_details('Delta','www.deltacorp.com','Chitubu','YW1213183BM',1235678235,"sme"));

/* * **************thes are login function  *****************
 * 1. check if the person loging in is not the super admin for the org
 * 2. if super admin - there should be only one  row in the tblUsers TABLE
 * 3. else log in as a usual user with set credentials
 * 4. ref to 2 - use admin for username and password. Upon validating allow to set new credentials 
 * anf that becomes number 2
 */

//basic  login function 
function AdminLogin($username, $password) {
    global $db;
    $status;
    try {

        $sql = $db->prepare('select * from tblUsers where (Username=? or EmailAddress=?) and password=? and deleted=0');
        $sql->execute(array($username, $username, $password));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
        if ($sql->rowCount() > 0) {
            $_SESSION['acc'] = $result[0]['UserID'];
            $_SESSION['Username'] = $result[0]['Username'];
            $_SESSION['Usergroup'] = $result[0]['UserType'];
            $status['status'] = 'passed';
        } else {
            $status['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $status['status'] = $ex->getMessage();
    }

    return $status;
}

//lock login
function LockLogin($username, $password) {
    global $db;
    $status;
    try {

        $sql = $db->prepare('select * from tblUsers where (Username=? or EmailAddress=?) and password=? and deleted=0');
        $sql->execute(array($username, $username, $password));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
        if ($sql->rowCount() > 0) {

            $status['status'] = 'passed';
        } else {
            $status['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $status['status'] = $ex->getMessage();
    }

    return $status;
}

function ResetPassword($UserID) {
    global $db;

    try {
        $password = "00000";
        $sql = $db->prepare('update tblUsers set Password=?,UpdatedDate=?,UpdatedBy=? where UserID=?');
        $sql->execute(array($password, date("Y-m-d H:i:s"), $_SESSION["acc"], $UserID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

/* * ***********************if user if logged in, the script will head straight to home page************* */

function Is_Logged_In() {
    if (isset($_SESSION['acc'])) {
        return true;
    }
}

//kill session for each users
function logout() {
    session_destroy();
    unset($_SESSION['acc']);
    return true;
}

//redirect function
//checks number of rows for users table during login
function get_Num_Of_Rows() {
    global $db;
    try {
        $sql = $db->prepare('select count(*) as num_of_rows from tblUsers');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
        $num_of_rows = $result[0]['num_of_rows'];
    } catch (Exception $ex) {
        $ex->getMessage();
    }

    return $num_of_rows;
}

/* * **********this function creates a user, the first on logger on deployment************* */

function Create_User($username, $password, $userfirstmame, $usersurname, $jobtitle, $emailadress, $user_group, $user_phone, $route_name,$CustomerVisitsTarget,$TargetSales,$TargetOrders,$TargetInvoices,$TargetHitRate,$TargetTotalTimeInField, $status, $created_by) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblUsers(Username,Password,UserFirstName,UserSurname,JobTitle,EmailAddress,UserType,UserPhoneNumber,RouteName,CustomerVisitsTarget,TargetSales,TargetOrders,TargetInvoices,TargetHitRate,TargetTotalTimeInField,Deleted,CreatedDate,CreatedBy) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($username, $password, $userfirstmame, $usersurname, $jobtitle, $emailadress, $user_group, $user_phone, $route_name,$CustomerVisitsTarget,$TargetSales,$TargetOrders,$TargetInvoices,$TargetHitRate,$TargetTotalTimeInField, $status, date('Y-m-d H:i:s'), $created_by));
        $counter = $sql->rowCount();
        $lastInsertId = $db->lastInsertId();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result['id'] = $lastInsertId;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function edit_user($username, $userfirstmame, $usersurname, $jobtitle, $emailadress, $user_group, $user_phone, $route_name, $user_ID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblUsers set Username=?,UserFirstName=?,UserSurname=?,JobTitle=?,EmailAddress=?,UserType=?,UserPhoneNumber=?,RouteName=?,UpdatedDate=?,UpdatedBy=? where UserID=?');
        $sql->execute(array($username, $userfirstmame, $usersurname, $jobtitle, $emailadress, $user_group, $user_phone, $route_name, date('Y-m-d H:i:s'), $_SESSION['acc'], $user_ID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function edit_user_pass($passkey, $user_ID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblUsers set Password=?,UpdatedDate=?,UpdatedBy=? where UserID=?');
        $sql->execute(array($passkey, date('Y-m-d H:i:s'), $_SESSION['acc'], $user_ID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//check if email has been used before to avoid duplication
function get_emails() {
    global $db;
    try {
        $sql = $db->prepare('select EmailAddress from tblUsers where deleted=0');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $ex->getMessage();
    }

    return $result;
}

//get logged in user details
function get_user_details() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblUsers where deleted=0 and UserID=?');
        $sql->execute(array($_SESSION['acc']));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//get logged in user details
function get_ext_userdetails($id) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblUsers where deleted=0 and UserID=?');
        $sql->execute(array($id));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//get logged in user details
function get_all_users() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblUsers where deleted=0 ');
        $sql->execute(array($_SESSION['acc']));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function get_all_users_expt_logged() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblUsers where deleted=0 and UserID!=?');
        $sql->execute(array($_SESSION['acc']));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}


function get_all_users_expt($UserID) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblUsers where deleted=0 and UserID!=?');
        $sql->execute(array($UserID));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}
/* * ****************CUSTOMER MODULE********************* */

//***************************************WE CREATE A CUSTOMER ACCOUNT by:*************************************/
//************ general details using function create_customer_general_details**************// done
//************ Account details using function update_customer_account_details**************//
////************Location details using function update_customer_location_details**************//

function gen_uuid() { //GENERATES uuid for each customer/customer table row for reference purposes
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function create_customer_general_details($cust_num, $cust_name, $cust_addr, $cust_tel, $cust_fax, $cust_email, $cust_cont_pers, $cust_contpsn_cell, $statusID, $cust_type) {
    global $db;
    $result = array();
    $default_path = "../customersuploads/default.jpg";
    try {
        $sql = $db->prepare('insert into tblCustomers(CustomerNumber,CustomerName,Address,Telephone,Fax,Email,ContactPerson,ContactCell,StatusID,CustomerType,Deleted,CreatedDate,CreatedBy,UNIQUEROWGUID,CustomerLogo) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($cust_num, $cust_name, $cust_addr, $cust_tel, $cust_fax, $cust_email, $cust_cont_pers, $cust_contpsn_cell, $statusID, $cust_type, 0, date("Y-m-d H:i:s"), $_SESSION['acc'], gen_uuid(), $default_path));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result['id'] = $db->lastInsertId();
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//set account value and settings for the customer
function update_customer_account_details($paymnt_method, $bpn, $vat_num, $acc_bal, $crdt_lim, $headroom, $trading_line, $risk_lvl, $cust_id) { //this function updates the customer based on ID returned from the above function
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblCustomers set [PaymentMethod]=?,[BPNumber]=?,[VATNumber]=?,[AccountBalance]=?,[CreditLimit]=?,Headroom=?,[KeyChain]=?,[RiskLevelID]=?,[UpdatedDate]=?,[UpdatedBy]=? where customerID=? and deleted=0');
        $sql->execute(array($paymnt_method, $bpn, $vat_num, $acc_bal, $crdt_lim, $headroom, $trading_line, $risk_lvl, date('Y-m-d H:i:s'), $_SESSION['acc'], $cust_id));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//this functions creates user localtion details given customer id
function update_customer_location_details($area_typeID, $city_id, $dist_id, $province_id, $route_id, $route_name,$NumberOfVisits, $cust_id) {
    global $db;
   
    try {
        $VisitFrequency = "Month";
        $sql = $db->prepare('update tblCustomers set [AreaTypeID]=?,[CityID]=?,[DistrictID]=?,[ProvinceID]=?,[RouteID]=?,RouteName=?,VisitFrequency=?,NumberOfVisits=?,[UpdatedDate]=?,[UpdatedBy]=? where customerID=? and deleted=0');
        $sql->execute(array($area_typeID, $city_id, $dist_id, $province_id, $route_id, $route_name,$VisitFrequency,$NumberOfVisits, date('Y-m-d H:i:s'), $_SESSION['acc'], $cust_id));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function UpdateCustSapRoute($route_name,$CustNum)
        {
     global $db;
    try {
        $sql = $db->prepare('update tblCustomers set RouteName=?,[UpdatedDate]=?,[UpdatedBy]=? where CustomerNumber=? and deleted=0');
        $sql->execute(array($route_name, date('Y-m-d H:i:s'), $_SESSION['acc'], $CustNum));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//this functions replicates the update press for the first function create_customer_general_details

function update_customer_general_details($cust_num, $cust_name, $cust_addr, $cust_tel, $cust_fax, $cust_email, $cust_cont_pers, $cust_contpsn_cell, $cust_type, $cust_id) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblCustomers set CustomerNumber=?,CustomerName=?,Address=?,Telephone=?,Fax=?,Email=?,ContactPerson=?,ContactCell=?,CustomerType=?,[UpdatedDate]=?,[UpdatedBy]=? where customerID=? and deleted=0');
        $sql->execute(array($cust_num, $cust_name, $cust_addr, $cust_tel, $cust_fax, $cust_email, $cust_cont_pers, $cust_contpsn_cell, $cust_type, date("Y-m-d H:i:s"), $_SESSION['acc'], $cust_id));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function update_customer_logo($pic_path, $cust_id) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblCustomers set CustomerLogo=? where customerID=? and deleted=0');
        $sql->execute(array($pic_path, $cust_id));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//print_r(update_customer_general_details('1-000ABC','Dealbano','Masakadza,Hatrman','1234567','1234','takunda@dealban.com','trey','123456','retailer',2596));

function deactivate_customer($cust_id) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblCustomers set deleted=?,StatusID=? where customerID=?');
        $sql->execute(array(0, 3, $cust_id));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function DeleteCustomer($RowID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('delete from tblCustomers where CustomerID=?');
        $sql->execute(array($RowID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//show all active customers
function show_all_customers() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("select * from tblCustomers where deleted=0 and (CustomerNumber!='NULL' or CustomerNumber!='')");
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//show customer details - single customer
function show_customer_details($cust_id) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblCustomers where deleted=0 and customerID=?');
        $sql->execute(array($cust_id));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function show_custdet($cust_name) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblCustomers where deleted=0 and CustomerName = ?');
        $sql->execute(array($cust_name));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function show_custIndata($cust_name) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblCustomers where deleted=0 and CustomerName like ?');
        $sql->execute(array('%'.$cust_name.'%'));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//the function shows all active customers for that account
function show_active_customers() {
    global $db;
    //$result=array();
    $state = "";
    try {
        $sql = $db->prepare('select * from tblCustomers where deleted=0 and CustomerNumber!=?');
        $sql->execute(array($state));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

/* * ***********************************************THIS ENDS THE CUSTOMER APIs********************************** */
/* * ************************************************STOCKING and WAREHOUSING BEGINS HERE***************************************** */
/*
 * 1. Add/edit and delete stocks
 * 2. allocate stocks to wareHOUSES based on stock on hand value
 * 3. on creation of stock determine units of measure and allocate to a dummy warehouse
 * 4. list all warehouse the product belongs to
 * 5. stock using base unit measures
 * 
 */

//this function creates a new product
function create_product($code, $desc, $prdctcatid, $availstocks, $ReOrderLevel, $excUntPri, $untPri, $prdctTypeID, $TaxCode, $unitOfMeasure, $isCont) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblProducts(Code,Description,ProductCategoryID,OnHand,ReOrderLevel,ExclUnitPrice,UnitPrice,ProductTypeID,TaxCode,Deleted,CreatedDate,CreatedBy,UnitMeasure,IsContainer) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($code, $desc, $prdctcatid, $availstocks, $ReOrderLevel, $excUntPri, $untPri, $prdctTypeID, $TaxCode, 0, date("Y-m-d H:i:s"), $_SESSION['acc'], $unitOfMeasure, $isCont));
        $counter = $sql->rowCount();
        $lastID = $db->lastInsertId();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result['id'] = $lastID;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//print_r(create_product("AH564", "Muriwo",1, 10, 10.67, 12,10.67, 10.67, 10.67, 10.67, 10,67, "", 4, "A", "EA"));

function show_all_stocks() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblProducts where deleted=0');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//print_r(show_all_stocks());

function edit_product($code, $desc, $prdctcatid, $availstocks, $reorderlevel, $excUntPri, $untPri, $prdctTypeID, $TaxCode, $unitOfMeasure, $isCont, $prod_id) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblProducts set Code=?,Description=?,ProductCategoryID=?,OnHand=?,ReOrderLevel=?,ExclUnitPrice=?,UnitPrice=?,ProductTypeID=?,TaxCode=?,UpdatedDate=?,UpdatedBy=?,UnitMeasure=?,IsContainer=? where ProductID=?');
        $sql->execute(array($code, $desc, $prdctcatid, $availstocks, $reorderlevel, $excUntPri, $untPri, $prdctTypeID, $TaxCode, date("Y-m-d H:i:s"), $_SESSION['acc'], $unitOfMeasure, $isCont, $prod_id));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//returns product detsails given its ID
function get_prod_det($prod_id) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblProducts where deleted=0 and ProductID=?');
        $sql->execute(array($prod_id));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}



function get_prod($pdctname) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblProducts where deleted=0 and Description=?');
        $sql->execute(array($pdctname));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}




function get_prod_inf($PdctCode) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblProducts where deleted=0 and Code=?');
        $sql->execute(array($PdctCode));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function get_prod_code($pdctname) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select Code from tblProducts where deleted=0 and Description=?');
        $sql->execute(array($pdctname));
        $result = $sql->fetchColumn();
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}


function get_out_of_stocks() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select count(*) as oos from tblProducts where deleted=0 and OnHand=0');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function get_all_SKUS() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select distinct code as code from tblProducts where deleted=0');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//this function creates a  new warehouse
function create_warehouse($warHsCode, $wrHseNm, $prntWrhsId) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblWareHouse(WareHouseCode,WareHouseName,CreatedDate,CreatedBy,ParentWareHouseID,StatusID) values (?,?,?,?,?,?)');
        $sql->execute(array($warHsCode, $wrHseNm, date("Y-m-d H:i:s"), $_SESSION['acc'], $prntWrhsId, 1));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_warehouses() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblWareHouse where statusID=1');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function get_warehouse_givenCode($code) 
{
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblWareHouse where statusID=1 and WareHouseCode=?');
        $sql->execute(array($code));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetWarehousesExcept($ThisWhseId, $parentWhseId) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblWareHouse where statusID=1 and (WareHouseID!=? and WareHouseID!=?)');
        $sql->execute(array($ThisWhseId, $parentWhseId));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetWarehousesExcld($ThisWhseId) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblWareHouse where statusID=1 and WareHouseID!=?');
        $sql->execute(array($ThisWhseId));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetWhseDetails($warehouseID) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblWareHouse where WareHouseID=? and statusID=1');
        $sql->execute(array($warehouseID));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function get_parent_whse($warehouseID) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select WareHouseName from tblWareHouse where WareHouseID=? and statusID=1');
        $sql->execute(array($warehouseID));
        $result = $sql->fetchColumn();
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//EDIT WAREHOUSWE
function EditWarehouse($warHsCode, $wrHseNm, $prntWrhsId, $WhseId) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblWareHouse set WareHouseCode=?,WareHouseName=?,UpdatedDate=?,UpdatedBy=?,ParentWareHouseID=? where [WareHouseID]=?');
        $sql->execute(array($warHsCode, $wrHseNm, date("Y-m-d H:i:s"), $_SESSION['acc'], $prntWrhsId, $WhseId));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//DEACTIVE WARFEHOUSE
function DeactivateWarehouse($WhseId) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblWareHouse set statusID=0, UpdatedDate=?, UpdatedBy=? where [WareHouseID]=?');
        $sql->execute(array(date("Y-m-d H:i:s"), $_SESSION['acc'], $WhseId));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function allocate_products($productID, $wholesaleID, $NoOfProducts, $notes) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblAllocateStocks(ProductID,WhoresaleID,NoOfProducts,notes,CreatedDate,CreatedBy) values (?,?,?,?,?,?)');
        $sql->execute(array($productID, $wholesaleID, $NoOfProducts, $notes, date("Y-m-d H:i:s"), $_SESSION['acc']));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_allocation_details($pid) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select *  from tblAllocateStocks where ProductID=?');
        $sql->execute(array($pid));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function AllocIDSTOCK($AID) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select NoOfProducts  from tblAllocateStocks where allocationID=?');
        $sql->execute(array($AID));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function ReduceStocksOnTransfer($amntAlloc, $AllocID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblAllocateStocks set NoOfProducts=? where allocationID=?');
        $sql->execute(array($amntAlloc, $AllocID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function RestoreWarehouseStock($amntAlloc, $AllocID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblAllocateStocks set NoOfProducts=NoOfProducts+? where allocationID=?');
        $sql->execute(array($amntAlloc, $AllocID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function DeleteObscoleteAllocation($AllocID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('delete from tblAllocateStocks where allocationID=?');
        $sql->execute(array($AllocID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function GetProdToBeAlloc($pid, $WsID) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select sum(NoOfProducts) as totalAvail  from tblAllocateStocks where ProductID=? and WhoresaleID=?');
        $sql->execute(array($pid, $WsID));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetWhseProd($pid, $WsID) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select *  from tblAllocateStocks where ProductID=? and WhoresaleID=? order by CreatedDate asc');
        $sql->execute(array($pid, $WsID));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetWhseProdGivnPname($WsID, $pname) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select *  from tblAllocateStocks where  WhoresaleID=? and ProductID=(select ProductID from tblProducts where Description =?) order by CreatedDate asc');
        $sql->execute(array($WsID, $pname));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//print_r(GetWhseProdGivnPname(3,"Call-Out Fee"));

function GetDistinctPid($WsID) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select distinct(ProductID)  from tblAllocateStocks where WhoresaleID=?');
        $sql->execute(array($WsID));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function GetWarehouseAllocation($Wid) {
    global $db;
    //$result=array();
    $instock = 0;
    try {
        $sql = $db->prepare('select *  from tblAllocateStocks where WhoresaleID=? and NoOfProducts!=?');
        $sql->execute(array($Wid, $instock));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function TotalWarehouseCapacity($Wid) {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select sum([NoOfProducts]) as TotalProducts from tblAllocateStocks where WhoresaleID=?');
        $sql->execute(array($Wid));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

/* * ******************************************************END OF STOCK MANAGEMENT ****************************************************************** */

// CREATION OF LOAD SHEETS
function create_new_route($routeNm, $routecode, $origin_city, $destination_city, $tgtDistanceKm, $targetDistMtrs, $AproxTimeHrs, $AproxTimeSec, $timePerStation, $numOfStations, $TotalTimeInSec, $RouteTotalTime) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblRoutes(RouteName,RouteCode,origin_city,Destination_City,TargetDistance,TargetDistanceMeters,AproxTime,AproxTimeSec,TimePerStation,NumOfStations,TotalTimeInSec,RouteTotalTimeInHrs,StatusID,CreatedDate,CreatedBy) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($routeNm, $routecode, $origin_city, $destination_city, $tgtDistanceKm, $targetDistMtrs, $AproxTimeHrs, $AproxTimeSec, $timePerStation, $numOfStations, $TotalTimeInSec, $RouteTotalTime, 1, date("Y-m-d H:i:s"), $_SESSION['acc']));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function LogCenters($RouteReference,$Sequence,$CenterDesc) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into luRouteCenters(RouteReference,Sequence,CenterDesc,DateCreated) values (?,?,?,?)');
        $sql->execute(array($RouteReference,$Sequence,$CenterDesc, date("Y-m-d H:i:s")));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}
//
function GetRouteHeaders(){
     global $db;
    try {

        $sql = $db->prepare('SELECT * from luRouteReference where RouteCode NOT IN (SELECT RouteCode from tblRoutes)');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function GetTempRtCd($RtName){
     global $db;
    try {

        $sql = $db->prepare('SELECT RouteCode from luRouteReference where Description = ?');
        $sql->execute(array($RtName));
        $result = $sql->fetchColumn();
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function CheckPreloads(){
     global $db;
    try {

        $sql = $db->prepare('SELECT * from luRouteReference');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}



//print_r(create_new_route('Inner City', 'ABCJJY', 'sasa,asacdakca,adniwogf,jvjsilvwubw,adiuhad','byo','harare', '567KM'));

function edit_route($routeNm, $routecode, $route_cities, $origin_city, $destination_city, $tgtDistance, $rtID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblRoutes set RouteName=?,RouteCode=?,RouteCities=?,origin_city=?,Destination_City=?,TargetDistance=?,UpdatedDate=?,UpdatedBy=? where RouteID=?');
        $sql->execute(array($routeNm, $routecode, $route_cities, $origin_city, $destination_city, $tgtDistance, date("Y-m-d H:i:s"), $_SESSION['acc'], $rtID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//returns route details given id
function get_single_route_details($route_id) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblRoutes where RouteID=?');
        $sql->execute(array($route_id));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

//print_r(get_single_route_details(72));
//reurns routes from
function get_route_details() {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblRoutes');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

//reurns routes from
function get_salesman_routes($userID) {
    global $db;
    try {

        $sql = $db->prepare('SELECT RouteName from tblUsers where UserID=?');
        $sql->execute(array($userID));
        $result = $sql->fetchColumn();
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function create_route_sheet($routename, $salesManID, $salesman, $vehRegNumber, $StartMileage, $endMileage, $copyID, $iz_fixed, $WarehouseId,$DepartureDate) {
    global $db;
    $status = 'Created';
    try {
        $sql = $db->prepare('insert into tblDailyRouteSheetHeader(RouteName,SalesManID,SalesMan,VehicleRegNumber,OpeningMileage,ClosingMileage,CopyID,IsFixedLoadSheet,RouteSheetStatus,SourceWarehouseID,DepartureDate,CreatedDate,CreatedBy) values (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($routename, $salesManID, $salesman, $vehRegNumber, $StartMileage, $endMileage, $copyID, $iz_fixed, $status, $WarehouseId,$DepartureDate, date("Y-m-d H:i:s"), $_SESSION['acc']));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $lastinsertID = $db->lastInsertId();
            $sheetNumber = sprintf('%06d', $lastinsertID);
            $this_sql = $db->prepare('update tblDailyRouteSheetHeader set SheetNumber=? where DailyRouteSheetHeaderID=?');
            $this_sql->execute(array($sheetNumber, $lastinsertID));
            $this_counter = $this_sql->rowCount();
            if ($this_counter > 0) {
                $result['status'] = 'ok';
                $result['id'] = $lastinsertID;
                $result['sheetNumber'] = $sheetNumber;
            } else {
                $result['status'] = 'inside_fail';
            }
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function edit_route_sheet($routeName, $salesManID, $salesman, $vehRegNumber, $rtstID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblDailyRouteSheetHeader set RouteName=?,SalesManID=?,SalesMan=?,VehicleRegNumber=?,UpdatedDate=?,UpdatedBy=? where DailyRouteSheetHeaderID=?');
        $sql->execute(array($routeName, $salesManID, $salesman, $vehRegNumber, date("Y-m-d H:i:s"), $_SESSION['acc'], $rtstID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_loadsheets() {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblDailyRouteSheetHeader');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function getActiveLoadsheets() {
    global $db;
    try {

        $sql = $db->prepare("SELECT * from tblDailyRouteSheetHeader where RouteSheetStatus!='Created' and RouteName!='Open'");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function GetLoadSheetToDeliver() {
    global $db;
    try {

        $sql = $db->prepare("SELECT * from tblDailyRouteSheetHeader where RouteSheetStatus='Created' or RouteSheetStatus='Loaded'  and RouteName!='Open'");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

// get invoices based on routesheet number

function get_invoicebasicinfo($routeSheetNumber) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from [tblInvoiceBasicInfo] where LoadSheetName=? and InvoiceTotal>0');
        $sql->execute(array($routeSheetNumber));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function GetInvoiceData($InvNum) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from [tblInvoiceBasicInfo] where InvoiceTotal>0 and InvoiceNum=?');
        $sql->execute(array($InvNum));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

/*


  print_r(get_invoicebasicinfo(000002));
  die();
 * 
 */

function update_ref_number($ref_num, $sheet_number) {
    global $db;
    try {

        $sql = $db->prepare('update tblDailyRouteSheetHeader set ReferenceNumber=? where SheetNumber=?');
        $sql->execute(array($ref_num, $sheet_number));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = "ok";
        } else {
            $result['status'] = "fail";
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function UpdateLoadStatus($Status, $sheet_number) {
    global $db;
    try {

        $sql = $db->prepare('update tblDailyRouteSheetHeader set RouteSheetStatus=?,UpdatedDate=?,UpdatedBy=? where SheetNumber=?');
        $sql->execute(array($Status,date("Y-m-d H:i:s"), $_SESSION["acc"], $sheet_number));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = "ok";
        } else {
            $result['status'] = "fail";
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_loadsheet_details($rsid) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblDailyRouteSheetHeader where DailyRouteSheetHeaderID=?');
        $sql->execute(array($rsid));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function GetSheetId($rsid) {
    global $db;
    try {

        $sql = $db->prepare('SELECT DailyRouteSheetHeaderID  from tblDailyRouteSheetHeader where SheetNumber=?');
        $sql->execute(array($rsid));
        $result = $sql->fetchColumn();
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function GetSheetDetGvnNum($rsid) {
    global $db;
    try {

        $sql = $db->prepare('SELECT *  from tblDailyRouteSheetHeader where SheetNumber=?');
        $sql->execute(array($rsid));
       $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function upload_loadsheet_products($salesman, $ProdCode,$productName,$TaxCode,$ExclPrice,$IncPrice, $iscontainer, $out_load, $route_sheet_num, $status) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblRouteSheetTruckLoadItems(SalesManName,ProdCode,ProductName,TaxCode,ExclPrice,IncPrice,iscontainer,Out,RouteSheetNumber,SyncStatus,CreatedDate,CreatedBy,STOCK_ROW_GUID) values (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($salesman, $ProdCode,$productName,$TaxCode,$ExclPrice,$IncPrice, $iscontainer, $out_load, $route_sheet_num, $status, date("Y-m-d H:i:s"), $_SESSION['acc'], gen_uuid()));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function updateLoadSheetQty($out_load, $ProdCode, $route_sheet_num) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblRouteSheetTruckLoadItems set Out = Out + ? where ProdCode = ? and RouteSheetNumber = ?');
        $sql->execute(array($out_load, $ProdCode, $route_sheet_num));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//print_r(GetProdToBeAlloc( 15 , 1));
function reduce_stocks($outload, $productID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblProducts set OnHand=OnHand-?,UpdatedDate=?,UpdatedBy=? where (ProductID=? or Code = ?)');
        $sql->execute(array($outload, date("Y-m-d H:i:s"), $_SESSION['acc'], $productID, $productID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_route_sheet_products($ldshtNum) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblRouteSheetTruckLoadItems where RouteSheetNumber=? ');
        $sql->execute(array($ldshtNum));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function checkLoadSheetProd($ldshtNum,$prodCode) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblRouteSheetTruckLoadItems where RouteSheetNumber=? and ProdCode = ? ');
        $sql->execute(array($ldshtNum,$prodCode));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}



function update_product_qty($cancelled_outload, $prod_name) {
    global $db;
    try {

        $sql = $db->prepare('update tblProducts set OnHand=OnHand+?,UpdatedDate=?, UpdatedBy=? where Description=?');
        $sql->execute(array($cancelled_outload, date('Y-m-d H:i:s'), $_SESSION['acc'], $prod_name));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'notok';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//print_r(get_route_sheet_products("015211"));


function cut_qty_on_cancel($cancelled_outload, $status, $prod_name, $routsheetNumber) {
    global $db;
    try {

        $sql = $db->prepare('update tblRouteSheetTruckLoadItems set Out=?,SyncStatus=?, UpdatedDate=?,UpdatedBy=? where ProductName=? and RouteSheetNumber=?');
        $sql->execute(array($cancelled_outload, $status, date("Y-m-d H:i:s"), $_SESSION['acc'], $prod_name, $routsheetNumber));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'notok';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function cancel_loadsheet($status, $cance_reason, $ldshtNum) {
    global $db;
    try {

        $sql = $db->prepare('update tblDailyRouteSheetHeader set RouteSheetStatus=?, CancellationReason=?, CancelledBy=?,CancelledDate=? where SheetNumber=?');
        $sql->execute(array($status, $cance_reason, $_SESSION['acc'], date('Y-m-d H:i:s'), $ldshtNum));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'notok';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function update_time_to_time_load_sheet_status($status, $sheetNumber) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblDailyRouteSheetHeader set RouteSheetStatus=?,UpdatedDate=?,UpdatedBy=? where SheetNumber=?');
        $sql->execute(array($status, date("Y-m-d H:i:s"), $_SESSION['acc'], $sheetNumber));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_loadedsheet_customers($routeName) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblCustomers where Deleted=0 and RouteName like ? ');
        $sql->execute(array('%'.$routeName.'%'));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

/* Order creations -----------
 * New Order
 * edit order
 * delete order
 * check order statuses and update when necessary

 */

function create_new_order($customerID, $customer_name, $salesManName) {
    global $db;
    $result = array();
    $order_status = "Created";
    try {
        $sql = $db->prepare('insert into tblCustomerOrders(CustomerID,CustomerName,SalesmanName,OrderDate,OrderStatus,Source,Syncronised,UniqueRowID,CreatedDate,CreatedBy) values (?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($customerID, $customer_name, $salesManName, date("Y-m-d H:i:s"), $order_status, 1, 0, gen_uuid(), date("Y-m-d H:i:s"), $_SESSION['acc']));
        $lastInsertID = $db->lastInsertId();
        $counter = $sql->rowCount();

        if ($counter > 0) {
            $new_id = sprintf('%06d', $lastInsertID);
            $order_no = 'SO' . $new_id;
            $sql_order = $db->prepare('update tblCustomerOrders set OrderNo = ? where CustomerOrderID = ?');
            $sql_order->execute(array($order_no, $lastInsertID));
            $order_counter = $sql_order->rowCount();
            if ($order_counter > 0) {
                $result['status'] = "ok";
                $result['id'] = $lastInsertID;
                $result['orderNo'] = $order_no;
            }
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//uploading orders from the mobile syncs
function create_mobi_order($orderNum, $customerID, $customerName, $order_date, $OrderTotal, $LoadSheetNumber) {
    global $db;
    $result = array();
    $order_status = "Created";
    try {
        $sql = $db->prepare('insert into tblCustomerOrders(OrderNo,CustomerID,CustomerName,OrderDate,OrderTotal,LoadSheetName,OrderStatus,Source,Syncronised,UniqueRowID,CreatedDate,CreatedBy) values (?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($orderNum, $customerID, $customerName, $order_date, $OrderTotal, $LoadSheetNumber, $order_status, 1, 0, gen_uuid(), date("Y-m-d H:i:s"), $_SESSION['acc']));

        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = "OK";
            $result['id'] = $db->lastInsertId();
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }

    return $result;
}

function uplad_mobi_order_prdcts($lastInsertID, $iteratorLineNo, $prodct_id, $product_name, $unit_price, $order_qty, $line_total,$GPSLat,$GPSLon) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblCustomerOrderDetails(CustomerOrderID,DetailLineNo,ProductID,ProductName,UnitPrice,Quantity,LineTotal,CreatedDate,CreatedBy,UniqueRowID,Syncronised,Source,GPSLat,GPSLon) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($lastInsertID, $iteratorLineNo, $prodct_id, $product_name, $unit_price, $order_qty, $line_total, date("Y-m-d H:i:s"), $_SESSION['acc'], gen_uuid(), 0, 1,$GPSLat,$GPSLon));
        if ($sql->rowCount() > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function delete_morder($order_num) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('delete from tblCustomerOrdersMobile where OrderNum = ?');
        $sql->execute(array($order_num));
        if ($sql->rowCount() > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function InsertWhseID($WhseID, $customerOrderID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblCustomerOrderDetails set WareHouseID=? where CustomerOrderID=?');
        $sql->execute(array($WhseID, $customerOrderID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function delete_morder_products($order_num) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('delete from tblCustomerOrderDetailsMobile where OrderNum = ?');
        $sql->execute(array($order_num));
        if ($sql->rowCount() > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

// mobi orders ends here


function set_order_products($lastInsertID, $iteratorLineNo, $prodct_id, $products_name, $unit_price, $order_qty, $line_total) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblCustomerOrderDetails(CustomerOrderID,DetailLineNo,ProductID,ProductName,UnitPrice,Quantity,LineTotal,CreatedDate,CreatedBy,UniqueRowID,Syncronised,Source) values (?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($lastInsertID, $iteratorLineNo, $prodct_id, $products_name, $unit_price, $order_qty, $line_total, date("Y-m-d H:i:s"), $_SESSION['acc'], gen_uuid(), 0, 1));
        if ($sql->rowCount() > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function insert_order_total($orderTotal, $customerOrderID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblCustomerOrders set OrderTotal=? where CustomerOrderID=?');
        $sql->execute(array($orderTotal, $customerOrderID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_orders() {

    global $db;
    try {

        $sql = $db->prepare('select * from tblCustomerOrders order by  OrderDate asc');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function get_order_details($customerOrderID) {
    global $db;
    try {

        $sql = $db->prepare('select * from tblCustomerOrderDetails where CustomerOrderID=?');
        $sql->execute(array($customerOrderID));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function get_customer_orders($customerID) {
    global $db;
    try {

        $sql = $db->prepare('select * from tblCustomerOrders where CustomerID=?');
        $sql->execute(array($customerID));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function create_log_details($CustomerOrderID, $log_message) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into luOrderLogs(CustomerOrderID,LogStatus,CreatedDate,CreatedBy) values (?,?,?,?)');
        $sql->execute(array($CustomerOrderID, $log_message, date("Y-m-d H:i:s"), $_SESSION['acc']));
        if ($sql->rowCount() > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function create_invoice($InvoiceNum, $invoiceDueDate, $InvoiceStatus, $invoiceTotal, $vatTotal, $SalesmanName, $PaymentMethod, $cust_name, $cust_rep, $proct_name, $prod_price, $prdctQTY, $prodctVAT, $prodctTotal, $taxcode, $discounamnt, $invoiceType, $TenderType, $tenderAmount, $VarAmnt, $gpsLat, $gpsLong) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblInvoices(InvoiceNum,InvoiceDate,InvoiceStatus,InvoiceTotal,TotalVAT,InvoiceSyncStatus,SalesmanName,PaymentMethod,CreatedDate,CreatedBy,CustomerName,CustomerRepName,ProductName,ProductPrice,ProductQty,ProductVAT,ProductTotal,ProductTaxCode,ProductDiscAmnt,InvoiceType,TenderType,TenderAmount,VarianceAmount,GPSLatitude,GPSLongitude) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($InvoiceNum, $invoiceDueDate, $InvoiceStatus, $invoiceTotal, $vatTotal, 1, $SalesmanName, $PaymentMethod, date('Y-m-d H:i:s'), $_SESSION['acc'], $cust_name, $cust_rep, $proct_name, $prod_price, $prdctQTY, $prodctVAT, $prodctTotal, $taxcode, $discounamnt, $invoiceType, $TenderType, $tenderAmount, $VarAmnt, $gpsLat, $gpsLong));
        if ($sql->rowCount() > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function create_inv_base_info($InvoiceNum, $invoiceDueDate, $InvoiceStatus, $invoiceTotal, $vatTotal, $del_date, $del_status, $SalesmanName, $cust_name, $OutStandingBal) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblInvoiceBasicInfo(InvoiceNum,InvoiceDate,InvoiceStatus,InvoiceTotal,TotalVAT,SyncStatus,ScheduledDeliveryDate,InvDeliveryStatus,SalesManName,CreatedDate,CreatedBy,CustomerName,OutstandingBalance) values (?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($InvoiceNum, $invoiceDueDate, $InvoiceStatus, $invoiceTotal, $vatTotal, 1, $del_date, $del_status, $SalesmanName, date('Y-m-d H:i:s'), $_SESSION['acc'], $cust_name, $OutStandingBal));
        if ($sql->rowCount() > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function show_invoices() {
    global $db;
    try {

        $sql = $db->prepare('select * from tblInvoiceBasicInfo where InvoiceTotal>0 order by CreatedDate desc');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function show_returns() {
    global $db;
    try {

        $sql = $db->prepare('select * from tblInvoiceBasicInfo where InvoiceTotal<=0 order by CreatedDate desc');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function order_processed_status($customerOrderID) {
    global $db;
    $result = array();
    $status = "Processed";
    try {
        $sql = $db->prepare('update tblCustomerOrders set OrderStatus=? where CustomerOrderID=?');
        $sql->execute(array($status, $customerOrderID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_order_logs($customerOrderID) {
    global $db;
    try {

        $sql = $db->prepare('select * from luOrderLogs where CustomerOrderID=? order by CreatedDate desc');
        $sql->execute(array($customerOrderID));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function create_currency_exchange_rates($currency, $CurrencyCode, $exchangerate) {
    global $db;
    try {

        $sql = $db->prepare('insert into tblCurrency(Currency,CurrencyCode,USDExchangeRate,DateSet,SetBy,status) values (?,?,?,?,?,?)');
        $sql->execute(array($currency, $CurrencyCode, $exchangerate, date('Y-m-d H:i:s'), $_SESSION['acc'], 'active'));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//create_currency_exchange_rates("United States Dollars","USA", 1);

//print_r(create_currency_exchange_rates('Zim Dollar',1));
//print_r(update_exchage_rate('Zim Dollar',0.98345566,1));

function update_exchage_rate($currency, $currency_code, $exchangerate, $ExchangeRateID) {
    global $db;
    try {

        $sql = $db->prepare('update tblCurrency set Currency=?,CurrencyCode=?,USDExchangeRate=?,UpdatedDate=?,UpdatedBy=? where ExchangeRateID=?');
        $sql->execute(array($currency, $currency_code, $exchangerate, date('Y-m-d H:i:s'), $_SESSION['acc'], $ExchangeRateID));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function delete_currency($ExchangeRateID) {
    global $db;
    try {

        $sql = $db->prepare("update tblCurrency set status='inactive',UpdatedDate=?,UpdatedBy=? where ExchangeRateID=?");
        $sql->execute(array(date('Y-m-d H:i:s'), $_SESSION['acc'], $ExchangeRateID));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function set_currency_logs($currency, $exchangerate, $note) {
    global $db;
    try {

        $sql = $db->prepare('insert into tblCurrencyUpdateLog(CurrencyName,ExchangeRate,DateLogged,LoggedBy,note) values (?,?,?,?,?)');
        $sql->execute(array($currency, $exchangerate, date('Y-m-d H:i:s'), $_SESSION['acc'], $note));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_tender_types() {
    global $db;
    try {

        $sql = $db->prepare("select * from tblCurrency where status='active'");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function create_invoice_tender($invNumber, $currency, $AmntTendered, $baseCurrencyEquiv) {
    global $db;
    try {

        $sql = $db->prepare('insert into tblInvoiceTender(InvNumber,Currency,AmountTendered,BaseCurrencyValue,DateSet,SetBy) values (?,?,?,?,?,?)');
        $sql->execute(array($invNumber, $currency, $AmntTendered, $baseCurrencyEquiv, date('Y-m-d H:i:s'), $_SESSION['acc']));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_invoice_tender($invNumber) {
    global $db;
    try {

        $sql = $db->prepare('select * from tblInvoiceTender where InvNumber=?');
        $sql->execute(array($invNumber));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function debit_credit_account($headroom, $accountbal, $custID) {
    global $db;
    try {

        $sql = $db->prepare('update tblCustomers set Headroom=?,AccountBalance=?,UpdatedDate=?,UpdatedBy=? where CustomerID=?');
        $sql->execute(array($headroom, $accountbal, date('Y-m-d H:i:s'), $_SESSION['acc'], $custID));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_invoice_details($invNum) {
    global $db;
    try {

        $sql = $db->prepare('select * from tblInvoices where InvoiceTotal>0 and InvoiceNum=?');
        $sql->execute(array($invNum));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}



function get_customer_invoices($customer_name) {
    global $db;
    try {

        $sql = $db->prepare('select * from tblInvoiceBasicInfo where InvoiceTotal>0 and CustomerName=?');
        $sql->execute(array($customer_name));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function UpdateInvoiceStatus($Status,$InvNumber) {
    global $db;
    try {

        $sql = $db->prepare('Update tblInvoiceBasicInfo set InvDeliveryStatus=? where InvoiceNum=?');
        $sql->execute(array($Status,$InvNumber));
       $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function get_accrued_customer_invoices($customer_name) {
    global $db;
    try {

        $sql = $db->prepare("select * from tblInvoiceBasicInfo where InvoiceTotal>0 and CustomerName=? and InvoiceStatus='Owing' order by [CreatedDate] ASC");
        $sql->execute(array($customer_name));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function get_debtors() {
    global $db;
    $status = "Owing";
    try {

        $sql = $db->prepare('select * from tblInvoiceBasicInfo where InvoiceStatus=? and InvoiceTotal>0');
        $sql->execute(array($status));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function upload_payment($customer_name, $amount, $payment_mode, $salesman, $lat, $longy, $balance) {
    global $db;
    try {
        $sql = $db->prepare('insert into tblRecievePayments(CustomerName,Amount,PaymentMode,SalesRep,CreatedDate,Status,Latitude,longitude,BalanceUnused) values (?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($customer_name, $amount, $payment_mode, $salesman, date('Y-m-d H:i:s'), "Unused", $lat, $longy, $balance));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result['status'] = 'ok';
            $result['id'] = $db->lastInsertId();
        } else {
            $result['status'] = 'fail';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function get_uploaded_payment() {
    global $db;

    try {

        $sql = $db->prepare('select * from tblRecievePayments');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function get_PAYMENT_Details($payment_id) {
    global $db;

    try {

        $sql = $db->prepare('select * from tblRecievePayments where paymentID=?');
        $sql->execute(array($payment_id));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function settle_invoice($oustandingbal, $status, $invoiceNum) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblInvoiceBasicInfo set OutstandingBalance=?, InvoiceStatus=? where InvoiceNum=?');
        $sql->execute(array($oustandingbal, $status, $invoiceNum));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function update_payment_data($invoices, $user_logged, $paymentID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblRecievePayments set ReasonsPaidFor=?,DateUsed=?,UsedBy=? where PaymentID=?');
        $sql->execute(array($invoices, date("Y-m-d H:i:s"), $user_logged, $paymentID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function update_payment_bal($new_bal, $status, $paymentID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblRecievePayments set BalanceUnused=?, Status=? where PaymentID=?');
        $sql->execute(array($new_bal, $status, $paymentID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function update_customer_account($headroom, $accountBalance, $customerName) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update  tblCustomers set Headroom=?,AccountBalance=? where CustomerName=?');
        $sql->execute(array($headroom, $accountBalance, $customerName));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

/*
 * Syncronisation from the mobile side
 * we start with orders
 * 
 */

function get_mobile_orders() {
    global $db;
    try {

        $sql = $db->prepare('select * from tblCustomerOrdersMobile');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function get_mobile_order_details($orderNum) {
    global $db;
    try {

        $sql = $db->prepare('select * from tblCustomerOrderDetailsMobile where OrderNum=?');
        $sql->execute(array($orderNum));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function CustomersWithNoNums() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("select * from tblCustomers where deleted=0 and (CustomerNumber='NULL' or CustomerNumber='')");
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function CustomersWithNoContact() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("select * from tblCustomers where deleted=0 and (Telephone is NULL or Telephone='')");
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function ShowProdWithNoPrices() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblProducts where deleted=0 and (ExclUnitPrice<=0 or UnitPrice<=0)');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

function ShowProdWithNoCode() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblProducts where deleted=0 and Code is NULL');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}



function get_synced_customers() {
    global $db;
    try {

        $sql = $db->prepare('select * from tblCustomers where (CustomerNumber=? or CustomerNumber=? or CustomerNumber=? or CustomerNumber=?)');
        $sql->execute(array("", NULL, "NULL", "null"));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

//print_r(get_synced_customers());

function get_used_payments($customer_name) {
    global $db;

    try {

        $sql = $db->prepare("select * from tblRecievePayments where Status='Used' and CustomerName=?");
        $sql->execute(array($customer_name));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

/* * ******************variables******************* */

function new_product_types($prod_type) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into luProductTypes (Description) values(?)');
        $sql->execute(array($prod_type));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function update_product_type($id, $new_value) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update luProductTypes set Description=? where ProductTypeID=?');
        $sql->execute(array($new_value, $id));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function new_product_category($prod_category) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into luProductCategories (Description) values(?)');
        $sql->execute(array($prod_category));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function update_product_cat($id, $new_value) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update luProductCategories set Description=? where ProductCategoryID=?');
        $sql->execute(array($new_value, $id));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function new_uom($uom) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into luUnitOM (UOMDesc) values(?)');
        $sql->execute(array($uom));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function update_uom($id, $new_value) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update luUnitOM set UOMDesc=? where uomID=?');
        $sql->execute(array($new_value, $id));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function new_usergrp($val) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into luUserGroups (Description) values(?)');
        $sql->execute(array($val));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function update_usrgrp($id, $new_value) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update luUserGroups set Description=? where UserGroupID=?');
        $sql->execute(array($new_value, $id));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function new_city($val) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into luCities (City) values(?)');
        $sql->execute(array($val));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

function update_city($id, $new_value) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update luCities set City=? where CityID=?');
        $sql->execute(array($new_value, $id));
        $count = $sql->rowCount();
        if ($count > 0) {
            $result["status"] = "ok";
        } else {
            $result["status"] = "fail";
        }
    } catch (Exception $ex) {
        $result["status"] = $ex->getMessage();
    }
    return $result;
}

//returns single truck details
function get_single_truck_details($truck_id) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblTrucks left join tblRouteUsers on tblRouteUsers.UserID = tblTrucks.DefaultDriverID where TruckID=?');
        $sql->execute(array($truck_id));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

//this function updates new truck
function edit_truck($code, $reg, $trailer, $interlink, $defaultDrivers, $UpdatedDate, $UpdatedBy, $color, $model, $odometer, $truck_id) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update tblTrucks set Code=?,RegNumber=?,Trailor=?,InterlinkTrailor=?,DefaultDriverID=?,UpdatedDate =?,UpdatedBy=?,Color=?,Model=?, Initial_Odometer_Reading =? where TruckID=?');
        $sql->execute(array($code, $reg, $trailer, $interlink, $defaultDrivers, $UpdatedDate, $UpdatedBy, $color, $model, $odometer, $truck_id));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//this function creates a new truck

function create_truc($ModeMake,$RegNumber,$DefaultDriverID,$Initial_Odometer_Reading) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('insert into tblTrucks(Code,RegNumber,DefaultDriverID,CreatedDate,CreatedBy,Initial_Odometer_Reading) values (?,?,?,?,?,?)');
        $sql->execute(array($ModeMake,$RegNumber,$DefaultDriverID,date("Y-m-d H:i:s"),$_SESSION["acc"],$Initial_Odometer_Reading));
        $counter = $sql->rowCount();
        $lastID = $db->lastInsertId();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result['id'] = $lastID;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}


function get_truck_details($RegNum) {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblTrucks where RegNumber=?');
        $sql->execute(array($RegNum));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        $counter = $sql->rowCount();
       
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function getTrucks() {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from tblTrucks where Deleted=0');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function CreateTransactions($CustomerID, $CustomerName, $TransType, $Description, $SystRef, $Reference, $Amount) {
    global $db;

    try {
        $sql = $db->prepare('insert into TblTransactions(CustomerID,CustomerName,TransType,Description,ReferenceID,Reference,Amount,Status,DateCreated,CreatedBy) values (?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($CustomerID, $CustomerName, $TransType, $Description, $SystRef, $Reference, $Amount, 1, date("Y-m-d H:i:s"), $_SESSION["acc"]));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function EditTransaction($TransType, $Description, $SystRef, $Reference, $Amount, $transID) {
    global $db;

    try {
        $sql = $db->prepare('update TblTransactions set TransType=?,Description=?,ReferenceID=?,Reference=?,Amount=?,UpdatedDate=?,UpdateBy=? where TransID=?');
        $sql->execute(array($TransType, $Description, $SystRef, $Reference, $Amount, date("Y-m-d H:i:s"), $_SESSION["acc"], $transID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function DeactivateTransaction($transID) {
    global $db;

    try {
        $sql = $db->prepare('update TblTransactions set status=?,UpdatedDate=?,UpdateBy=? where TransID=?');
        $sql->execute(array(0, date("Y-m-d H:i:s"), $_SESSION["acc"], $transID));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function list_transactions() {
    global $db;
    try {

        $sql = $db->prepare('SELECT * from TblTransactions where Status=1');
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

//get payments, invoices and transactions
// TransDate, CustName,Reference-type,Amount
function GetValueTransactions($customerName, $customerNam, $customerNm) {
    global $db;
    try {

        $stmnt = "
                    SELECT CustomerName as CustName, TransType as Ttype, Amount as Amnt,DateCreated as CrtdDt, class from TblTransactions where CustomerName=? and Status=1  
            UNION
                    SELECT CustomerName as CustName, PaymentMode as Ttype, Amount as Amnt,CreatedDate as CrtdDt, class  from tblRecievePayments where CustomerName=?
            UNION
                    SELECT CustomerName as CustName, InvoiceNum as Ttype, InvoiceTotal as Amnt, CreatedDate as CrtdDt,class from tblInvoiceBasicInfo where InvoiceTotal>0 and CustomerName=?
                    Order by CrtdDt ASC";

        $sql = $db->prepare($stmnt);
        $sql->execute(array($customerName, $customerNam, $customerNm));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function CreateErpConnection($ErpName, $ConnSummary, $Status) {
    global $db;

    try {
        $sql = $db->prepare('insert into TblCreateErpConnection(ERPName,SummaryConnection,Status,AttemptBy,AttemptDate) values (?,?,?,?,?)');
        $sql->execute(array($ErpName, $ConnSummary, $Status, $_SESSION["acc"], date("Y-m-d H:i:s")));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function GetERPData($ERPName) {
    global $db;
    try {
        if ($ERPName == "SAP") {
            $sql = $db->prepare("SELECT * from TblCreateErpConnection where ERPName=? and status='ConnectionSuccessed'");
        } else {
            $sql = $db->prepare("SELECT * from TblCreateErpConnection where ERPName=?");
        }
        $sql->execute(array($ERPName));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function GetDataGvnCon($ConID) {
    global $db;
    try {

        $sql = $db->prepare("SELECT * from TblCreateErpConnection where ConnectionID=?");
        $sql->execute(array($ConID));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }
    return $result;
}

function UploadProdFromSap($code, $desc,$TaxCode,$ProdTypeID, $unitOfMeasure) {
    global $db;

    try {
        $db->beginTransaction();

        $sql_getData = $db->prepare("SELECT * from tblProducts where Code=?");
        $sql_getData->execute(array($code));
        $result_getData = $sql_getData->fetchAll(PDO::FETCH_ASSOC);

        if (sizeof($result_getData) > 0) {
            $sql = $db->prepare('update tblProducts set Description=?,TaxCode=?,ProductTypeID=?,UpdatedDate=?,UpdatedBy=?,UnitMeasure=? where Code=? ');
            $sql->execute(array($desc,$TaxCode,$ProdTypeID, date("Y-m-d H:i:s"), $_SESSION['acc'], $unitOfMeasure, $code));
             $sql->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
            $counter = $sql->rowCount();
           
            if ($counter > 0) {
                $result['status'] = 'ok';
                //  $result['id'] = $lastID;
            } else {
                $result['status'] = 'failed';
            }
        } else {

            $sql = $db->prepare('insert into tblProducts(Code,Description,TaxCode,ProductTypeID,Deleted,CreatedDate,CreatedBy,UnitMeasure) values (?,?,?,?,?,?,?,?)');
            $sql->execute(array($code, $desc, $TaxCode,$ProdTypeID, 0, date("Y-m-d H:i:s"), $_SESSION['acc'], $unitOfMeasure));
            $counter = $sql->rowCount();
            $lastID = $db->lastInsertId();
            if ($counter > 0) {
                $result['status'] = 'ok';
                // $result['id'] = $lastID;
            } else {
                $result['status'] = 'failed';
            }
        }
        $db->commit();
    } catch (Exception $ex) {
        $db->rollBack();
        $result['status'] = $ex->getMessage();
    }
    return $result;
}



function UpdatePrice($ExclPrice,$Pcode) {
    global $db;

    try {
        $Det = get_prod_inf($Pcode);
        $TaxCode = $Det[0]["TaxCode"];
        if($TaxCode == "A")
        {
         $UnitPrice =  $ExclPrice * 1.15;  
        }
        else{
             $UnitPrice =  $ExclPrice;
        }
        $sql = $db->prepare('update tblProducts set ExclUnitPrice=?,UnitPrice=?,UpdatedDate=?,UpdatedBy=? where Code=?');
        $sql->execute(array($ExclPrice,$UnitPrice,date("Y-m-d H:i:s"), $_SESSION["acc"],$Pcode));
        $counter = $sql->rowCount();
        if ($counter > 0) 
            {
            $result['status'] = 'ok';
        } else
            {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//print_r(UpdatePrice(0.99,"132006"));

function UpdateQtyFromSap($Qty, $Code) {
    global $db;

    try {
        $sql = $db->prepare('update tblProducts set OnHand = OnHand+?,UpdatedDate=?,UpdatedBy=? where Code=?');
        $sql->execute(array($Qty, date("Y-m-d H:i:s"), $_SESSION['acc'], $Code));
        $counter = $sql->rowCount();

        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $db->rollBack();
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function DeleteAllocOnQtyUpdt($ProdID, $WhseID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('delete from tblAllocateStocks where ProductID=? and WhoresaleID=?');
        $sql->execute(array($ProdID, $WhseID));
        $counter = $sql->rowCount();
       $result['status'] = 'ok';
        
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function sysncCustomerFromSap($CustNum,$cust_name, $cust_addr, $cust_tel, $cust_fax, $cust_email, $cust_cont_pers, $cust_contpsn_cell, $statusID, $cust_type,$paymnt_method, $bpn, $vat_num, $acc_bal, $crdt_lim, $headroom, $trading_line, $risk_lvl)
        {
    global $db;
    try{
    $sql_getData = $db->prepare("SELECT * from tblCustomers where CustomerNumber=?");
    $sql_getData->execute(array($CustNum));
    $result_getData = $sql_getData->fetchAll(PDO::FETCH_ASSOC);
    if(sizeof($result_getData)>0)
    {
        
        $update = update_customer_account_details($paymnt_method, $bpn, $vat_num, $acc_bal, $crdt_lim, $headroom, $trading_line, $risk_lvl,$cust_id);
     if($update["status"]=="ok")
     {
         $rslt["status"]="ok";
     }
     else{
          $rslt["status"]="Updatefail";
     }
    }
    else{
     $insrt =   create_customer_general_details($CustNum, $cust_name, $cust_addr, $cust_tel, $cust_fax, $cust_email, $cust_cont_pers, $cust_contpsn_cell, $statusID, $cust_type);
   if($insrt["status"]=="ok"){
     $cust_id = $insrt["id"];
     $update = update_customer_account_details($paymnt_method, $bpn, $vat_num, $acc_bal, $crdt_lim, $headroom, $trading_line, $risk_lvl,$cust_id);
     if($update["status"]=="ok")
     {
         $rslt["status"]="ok";
     }
     else{
          $rslt["status"]="Updatefail";
     }
     
   }
    else{
          $rslt["status"]="CreateFail";
     }
    }
    }
 catch (Exception $ex){
     $rslt = $ex->getMessage();
 }
    return $rslt;
}

function UploadSapPricelist($ErpName, $ErpPricingCon, $ProdCode, $ExclPrice,$PriceListRef){
     global $db;

    try {
       
        $sql = $db->prepare('insert into TblErpPriceLists(ERP,ErpPricingCondition,ProductCode,ExclusivePrice,PriceListReference,CreatedBy,CreatedDate) values (?,?,?,?,?,?,?)');
        $sql->execute(array($ErpName, $ErpPricingCon, $ProdCode, $ExclPrice,$PriceListRef, $_SESSION["acc"], date("Y-m-d H:i:s")));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

Function deleteSAmePricingCon($PricingCon){
   
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('delete from TblErpPriceLists where ErpPricingCondition=?');
        $sql->execute(array($PricingCon));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

Function GetPriceCount(){
   
    global $db;
  
    try {
        $sql = $db->prepare("select count(*) as Tot from TblErpPriceLists where IsActive='Yes'");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

Function GetPrices($ProdCode){
   
    global $db;
  
    try {
        $sql = $db->prepare("select * from TblErpPriceLists where ProductCode = ?");
        $sql->execute(array($ProdCode));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

function UploadSapCreditLimits($cntrlArea,$SapCustomerNumber,$custnum,$credLimit,$credexposure){
  global $db;

    try {
       
        $sql = $db->prepare('insert into TblSapCreditLimits(ControlArea,SAPCustomerNumber,CustomerNumber,CreditLimit,CreditExposure,SyncBy,DateSynced) values (?,?,?,?,?,?,?)');
        $sql->execute(array($cntrlArea,$SapCustomerNumber,$custnum,$credLimit,$credexposure,$_SESSION["acc"], date("Y-m-d H:i:s")));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}

Function GetCustCreditData(){
   
    global $db;
  
    try {
        $sql = $db->prepare("select * from TblSapCreditLimits");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

Function DeleteCreditInfo(){
   
    global $db;
  
    try {
        $sql = $db->prepare("delete from TblSapCreditLimits");
        $sql->execute();
         $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

function GetTempRouteName($Code){
     global $db;
  
    try {
        $sql = $db->prepare("select Description from luRouteReference where RouteCode=?");
        $sql->execute(array($Code));
        $result = $sql->fetchColumn();
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}


/****************************************Invoice deliveries*************************************/

function CreateDelivery($InvoiceNum,$InvoiceDate,$InvoiceStatus,$InvoiceTotal,$InvoiceSyncStatus,$TotalVAT,$SalesmanName,$PaymentMethod,$CustomerName,$CustomerRepName,$ProductName,$ProductPrice,$ProductQty,$ProductTotal,$ProductTaxCode,$ProductVAT,$ProductDiscAmnt,$InvoiceType,$TenderType,$TenderAmount,$VarianceAmount,$RouteSheetNumber,$Deliverer){
  global $db;

    try {
        $sql = $db->prepare('insert into tblDeliveries(InvoiceNum,InvoiceDate,InvoiceStatus,InvoiceTotal,InvoiceSyncStatus,TotalVAT,SalesmanName,PaymentMethod,CreatedDate,CreatedBy,CustomerName,CustomerRepName,ProductName,ProductPrice,ProductQty,ProductTotal,ProductTaxCode,ProductVAT,ProductDiscAmnt,InvoiceType,TenderType,TenderAmount,VarianceAmount,RouteSheetNumber,Deliverer) values (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($InvoiceNum,$InvoiceDate,$InvoiceStatus,$InvoiceTotal,$InvoiceSyncStatus,$TotalVAT,$SalesmanName,$PaymentMethod,date("Y-m-d H:i:s"),$_SESSION["acc"],$CustomerName,$CustomerRepName,$ProductName,$ProductPrice,$ProductQty,$ProductTotal,$ProductTaxCode,$ProductVAT,$ProductDiscAmnt,$InvoiceType,$TenderType,$TenderAmount,$VarianceAmount,$RouteSheetNumber,$Deliverer));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}

Function GetLoadDeliveries($SheetNumber){
   
    global $db;
  
    try {
        $sql = $db->prepare("select * from tblDeliveries where RouteSheetNumber = ?");
        $sql->execute(array($SheetNumber));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

function ViewDeliveries() {
    global $db;

    try {

        $sql = $db->prepare("select distinct(InvoiceNum),InvoiceStatus,InvoiceTotal,SalesmanName,RouteSheetNumber,CustomerName,DateDelivered,CreatedDate from [tblDeliveries]  where SalesmanName is NOT NULL and SalesmanName != ? and RouteSheetNumber is NOT NULL and RouteSheetNumber != ?");
        $sql->execute(array("",""));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

//print_r(ViewDeliveries());

function GetDeliveryDetails($InvNum) {
    global $db;

    try {

        $sql = $db->prepare("select * from [tblDeliveries] where InvoiceNum=?");
        $sql->execute(array($InvNum));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function getDeliveredCustomers(){
      global $db;

    try {

        $sql = $db->prepare("select distinct(CustomerName) as CustomerName FROM [tblDeliveries]");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;       
}

Function GetDistinctInv($SheetNumber){
   
    global $db;
  
    try {
        $sql = $db->prepare("select Distinct(InvoiceNum) from tblDeliveries where RouteSheetNumber = ?");
        $sql->execute(array($SheetNumber));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

Function GetDistInv($SheetNumber){
   
    global $db;
  
    try {
        $sql = $db->prepare("select Distinct(InvoiceNum) from tblInvoiceBasicInfo where LoadSheetName = ?");
        $sql->execute(array($SheetNumber));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

Function GetInvData($InvNum){
   
    global $db;
  
    try {
        $sql = $db->prepare("select * from tblDeliveries where InvoiceNum = ?");
        $sql->execute(array($InvNum));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;

}

function DeleteDelivery($RouteSheetNumber){
  global $db;

    try {
        $sql = $db->prepare('delete from tblDeliveries where RouteSheetNumber = ?');
        $sql->execute(array($RouteSheetNumber));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}

function DeleteDefinedDelivery($RouteSheetNumber,$InvNumber){
  global $db;

    try {
        $sql = $db->prepare('delete from tblDeliveries where RouteSheetNumber = ? and InvoiceNum=?');
        $sql->execute(array($RouteSheetNumber,$InvNumber));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}

function InvoicesToSync(){
     
    global $db;
  
    try {
        $Synced =1;
        $Status = "Return";
        $sql = $db->prepare("select * from tblInvoices where ErpSync!=? and InvoiceStatus!=?");
        $sql->execute(array($Synced,$Status));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}


function CreateSyncDocLog($DocNumber,$ProductCode,$QtyOnDocument){
  global $db;

    try {
        $sql = $db->prepare('insert into luSapSyncRecords(DocNumber,ProductCode,QtyOnDocument,DateCreated,CreatedBy) values (?,?,?,?,?)');
        $sql->execute(array($DocNumber,$ProductCode,$QtyOnDocument,date("Y-m-d H:i:s"),$_SESSION["acc"]));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}

function GetDistDocNums(){
     
    global $db;
  
    try {
       
        $sql = $db->prepare("select DocNumber from luSapSyncRecords");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}

function SetCustomerVisitSchedule($CustomerName,$LoadSheetNumber)
        {
      global $db;

    try {
        $sql = $db->prepare('insert into tblCustomerVisitCustomers(CustomerName,LoadSheetNumber,DateCreated,CreatedBy) values (?,?,?,?)');
        $sql->execute(array($CustomerName,$LoadSheetNumber,date("Y-m-d H:i:s"),$_SESSION["acc"]));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}


function create_salesmanTarget($salesman,$january,$february,$march,$april,$may,$june,$july,$august,$september,$october,$november,$december,$salid)  {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update salesmanTargets set Salesman=?,january=?, february=?,march=?,april=?,may=?,june=?,july=?,august=?,september=?,october=?,november=?,december=? where Salesman =?');
        $sql->execute(array($salesman,$january,$february,$march,$april,$may,$june,$july,$august,$september,$october,$november,$december,$salid));
        $counter = $sql->rowCount();
		$lastID = $db->lastInsertId();
        if ($counter > 0) {
            $result['status'] = 'ok';
			$result['id'] = $lastID;
			
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}
//print_r(create_salesmanTarget('Takunda',12,34,33,45,67,45,89,23,45,23,34,78,'Takunda'));


function create_customTarget($customers,$january,$february,$march,$april,$may,$june,$july,$august,$september,$october,$november,$december,$cID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update customerTargets set Customer=?,January=?, February=?,March=?,April=?,May=?,June=?,July=?,August=?,September=?,October=?,November=?,December=? where Customer =?');
        $sql->execute(array($customers,$january,$february,$march,$april,$may,$june,$july,$august,$september,$october,$november,$december,$cID));
        $counter = $sql->rowCount();
		$lastID = $db->lastInsertId();
        if ($counter > 0) {
            $result['status'] = 'ok';
			$result['id'] = $lastID;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}
//print_r(create_customTarget("NHENGA",21,2,34,67,12,45,44,56,67,75,43,24,"NHENGA"));

function create_productTarget($product,$january,$february,$march,$april,$may,$june,$july,$august,$september,$october,$november,$december,$pID) {
    global $db;
    $result = array();
    try {
        $sql = $db->prepare('update aximos.dbo.productsTargets set product =?,january=?, february=?,march=?,april=?,may=?,june=?,july=?,august=?,september=?,october=?,november=?,december=? WHERE product =?');
        $sql->execute(array($product,$january,$february,$march,$april,$may,$june,$july,$august,$september,$october,$november,$december,$pID));
        $counter = $sql->rowCount();
		$lastID = $db->lastInsertId();
        if ($counter > 0) {
            $result['status'] = 'ok';
			$result['id'] = $lastID;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;
}
//print_r( create_productTarget(' Banana Flavoured yoghurt 1 x 20L',12,34,43,67,56,' Banana Flavoured yoghurt 1 x 20L'));

//show all active customers
function show_all_producttargets() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("select * from dbo.productsTargets where product != ' ' ");
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

//show all active customers
function show_all_salesmantargets() {
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("select * from salesmanTargets where Salesman != ' ' and Salesman in ( select Username from tblUsers) and year = ?");
        $sql->execute(array(date("Y")));
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}


function show_all_targets() 
{
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare("SELECT * FROM customerTargets where Customer != ''");
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}


function get_promo_details() 
{
    global $db;
    //$result=array();
    try {
        $sql = $db->prepare('select * from tblPromotions');
        $sql->execute();
        $result = $sql->fetchALL(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result = $ex->getMessage();
    }

    return $result;
}

Function ViewClientVisits()
{
    global $db;
  
    try {
      
        $sql = $db->prepare("select * from [tblClientVisits]");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result; 
}

Function ViewSuggestions()
{
    global $db;
  
    try {
      
        $sql = $db->prepare("select * from [tblSuggestionBox]");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result; 
}

Function GetLasInsert($fieldname,$tableName){
     global $db;
  
    try {
      
        $sql = $db->prepare("select max($fieldname) as MaxID from $tableName");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        }
        catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result; 
}

function GetAddres($lat,$lon){
 global $db;
    try {
      
        $sql = $db->prepare("SELECT  Address from  gpscron where Lat=? and lon=?");
        $sql->execute(array($lat,$lon));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;    
}



function GPSCron($TableName,$lat,$lon){
   global $db;
    try {
        $val = "0.0";
        $sql = $db->prepare("SELECT $lat,$lon from  $TableName where ($lat <> ?  and  $lat not in (select Lat from gpscron)) and ($lon <> ? and $lon not in (select lon from gpscron)) group by $lat,$lon");
        $sql->execute(array($val,$val));
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;      
}

function SetGPs($Lat,$lon,$Address)
{
   global $db;

    try {
        $sql = $db->prepare('insert into gpscron(Lat,lon,Address) values (?,?,?)');
        $sql->execute(array($Lat,$lon,$Address));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;     
}

function getWithNullAddress(){
     global $db;
    try {
      
        $sql = $db->prepare("SELECT Lat,lon from  gpscron where Address is NULL");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result; 
}

function UpdateGPs($Address,$Lat,$lon)
{
   global $db;

    try {
        $sql = $db->prepare('update gpscron set Address = ? where Lat = ? and lon = ?');
        $sql->execute(array($Address,$Lat,$lon));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;     
}


Function ConfigureLicenseSettings($UnitOfMeasure,$PricePerUOM){
   global $db;
    try {
        $sql = $db->prepare('insert into tblLicenseSettings(UnitOfMeasure,PricePerUOM,DateSet,CreatedBy) values (?,?,?,?)');
        $sql->execute(array($UnitOfMeasure,$PricePerUOM,date("Y-m-d H:i:s"),$_SESSION["acc"]));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;       
}

Function UpdateConfigs($UnitOfMeasure,$PricePerUOM){
  global $db;
    try {
        $sql = $db->prepare('update tblLicenseSettings set UnitOfMeasure=?,PricePerUOM=?,DateSet=?,CreatedBy=?');
        $sql->execute(array($UnitOfMeasure,$PricePerUOM,date("Y-m-d H:i:s"),$_SESSION["acc"]));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}

function LicenseSettings(){
 global $db;
    try {
      
        $sql = $db->prepare("SELECT * from  tblLicenseSettings");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;    
}

function LogConfigs($OldUOM,$UnitOfMeasure,$OldPrice,$PricePerUOM,$Summary){
    global $db;
    try {
        $sql = $db->prepare('insert into tblLicenseSettingsLogs(OldUOM,UnitOfMeasure,OldPricePerUOM,NewPricePerUOM,Summary,DateSet,CreatedBy) values (?,?,?,?,?,?,?)');
        $sql->execute(array($OldUOM,$UnitOfMeasure,$OldPrice,$PricePerUOM,$Summary,date("Y-m-d H:i:s"),$_SESSION["acc"]));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;    
}

function viewlicenseSettingsLogs(){
 global $db;
    try {
      
        $sql = $db->prepare("SELECT * from  tblLicenseSettingsLogs");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;    
}

/****************************************ISSUANCE OF A LICENSE********************************/
function IssueLicense($CompanyName,$StartDate,$ExpiryDatedate)
{
    global $db;
    try {
        $sql = $db->prepare('insert into tblLicenses(CompanyName,StartDate,ExpiryDate,UpdateBy,UpdateDate) values (?,?,?,?,?)');
        $sql->execute(array($CompanyName,$StartDate,$ExpiryDatedate,$_SESSION["acc"],date("Y-m-d H:i:s")));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;  
}

function UpdateLicense($CompanyName,$StartDate,$ExpiryDatedate)
{
 global $db;
    try {
        $sql = $db->prepare('update tblLicenses  set CompanyName = ?,StartDate=?,ExpiryDate=?,UpdateBy=?,UpdateDate=?');
        $sql->execute(array($CompanyName,$StartDate,$ExpiryDatedate,$_SESSION["acc"],date("Y-m-d H:i:s")));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;     
}



Function LicenseDetails()
{
    global $db;
    try {
      
        $sql = $db->prepare("SELECT * from  tblLicenses");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}



function UpdateIssuesLogs($LicenseID,$AmountPaid,$UOM,$CostPerUOM,$QtyOnUOM,$Datefrom,$DateTo,$Summary)
{
    global $db;
    try {
        $sql = $db->prepare('insert into tblLicenseLogs(LicenseID,AmountPaid,UOM,CostPerUOM,QtyOnUOM,Datefrom,DateTo,Summary,DoneBy,DateDone) values (?,?,?,?,?,?,?,?,?,?)');
        $sql->execute(array($LicenseID,$AmountPaid,$UOM,$CostPerUOM,$QtyOnUOM,$Datefrom,$DateTo,$Summary,$_SESSION["acc"],date("Y-m-d H:i:s")));
        $counter = $sql->rowCount();
        if ($counter > 0) {
            $result['status'] = 'ok';
            $result["counter"] = $counter;
        } else {
            $result['status'] = 'failed';
        }
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;  
}

Function ViewLicenseIssuesLog()
{
    global $db;
    try {
      
        $sql = $db->prepare("SELECT * from  tblLicenseLogs");
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $ex) {
        $result['status'] = $ex->getMessage();
    }
    return $result;   
}



ob_end_flush();
