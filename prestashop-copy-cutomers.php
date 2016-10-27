<?php
header('Content-Type: text/html; charset=utf-8');

// Backup before use! It will only work if the _COOKIE_KEY_ is the same on both sides.

/* source */
$servername_source = "localhost";
$username_source = "";
$password_source = "";
$dbname_source = "";

/* destination */
$servername_destination = "localhost";
$username_destination = "";
$password_destination = "";
$dbname_destination = "";

$prefix = 'ps_';
$id_shop = 3; //insert shop id
$id_group = 3; //insert group id

$conn_source = new mysqli($servername_source, $username_source, $password_source, $dbname_source);
if ($conn_source->connect_error) {
    die("Connection failed: " . $conn_source->connect_error);
}

$conn_destination = new mysqli($servername_destination, $username_destination, $password_destination, $dbname_destination);
if ($conn_destination->connect_error) {
    die("Connection failed: " . $conn_destination->connect_error);
}

$result = $conn_source->query("SELECT * FROM " . $prefix . "customer");

$counter_exist = $counter_non_exist = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $id_customer = getIdCustomer($row["email"], $conn_destination, $prefix);
        if (!$id_customer) {
            $counter_non_exist++;
            echo 'Custumer (' . $row["email"] . ') does not exist in database, insert it.<br>';

            $sql_insert_customer = "INSERT INTO `" . $prefix . "customer` (
                `id_shop`,
                `id_gender`,
                `id_default_group`,
                `id_lang`,
                `firstname`,
                `lastname`,
                `email`,
                `passwd`,
                `last_passwd_gen`,
                `birthday`,
                `newsletter`,
                `ip_registration_newsletter`,
                `newsletter_date_add`,
                `optin`,
                `secure_key`,
                `active`,
                `date_add`,
                `date_upd`)
            VALUES (
                " . $id_shop . ",
                " . $row['id_gender'] . ",
                " . $id_group . ",
                " . $row['id_lang'] . ",
                '" . cleanInput($conn_destination, $row['firstname']) . "',
                '" . cleanInput($conn_destination, $row['lastname']) . "',
                '" . $row['email'] . "',
                '" . $row['passwd'] . "',
                '" . $row['last_passwd_gen'] . "',
                '" . $row['birthday'] . "',
                " . $row['newsletter'] . ",
                '" . $row['ip_registration_newsletter'] . "',
                '" . $row['newsletter_date_add'] . "',
                " . $row['optin'] . ",
                '" . $row['secure_key'] . "',
                1,
                '" . $row['date_add'] . "',
                '" . $row['date_upd'] . "'
            )";

            if ($conn_destination->query($sql_insert_customer) === TRUE) {

                $new_id_customer = getIdCustomer($row["email"], $conn_destination, $prefix);
                if ($new_id_customer) {

                    if ($conn_destination->query("INSERT INTO `" . $prefix . "customer_group` (`id_customer`, `id_group`) VALUES ($new_id_customer, $id_group)") === TRUE) {

                    } else {
                        echo "Error: " . $sql . "<br>" . $conn_destination->error;
                        die();
                    }

                    $sql_address = "SELECT * FROM " . $prefix . "address WHERE id_customer ='" . $row["id_customer"] . "'";
                    $result_address = $conn_source->query($sql_address);

                    if ($result_address->num_rows > 0) {
                        while ($row_address = $result_address->fetch_assoc()) {

                            $sql_insert_address = "INSERT INTO `" . $prefix . "address` (
                                `id_country`,
                                `id_state`,
                                `id_customer`,
                                `alias`,
                                `company`,
                                `lastname`,
                                `firstname`,
                                `address1`,
                                `address2`,
                                `postcode`,
                                `city`,
                                `other`,
                                `phone`,
                                `phone_mobile`,
                                `vat_number`,
                                `dni`,
                                `date_add`,
                                `date_upd`,
                                `deleted`
                            ) VALUES(
                                " . $row_address['id_country'] . ",
                                " . $row_address['id_state'] . ",
                                " . $new_id_customer . ",
                                '" . cleanInput($conn_destination, $row_address['alias']) . "',
                                '" . cleanInput($conn_destination, $row_address['company']) . "',
                                '" . cleanInput($conn_destination, $row_address['lastname']) . "',
                                '" . cleanInput($conn_destination, $row_address['firstname']) . "',
                                '" . cleanInput($conn_destination, $row_address['address1']) . "',
                                '" . cleanInput($conn_destination, $row_address['address2']) . "',
                                '" . cleanInput($conn_destination, $row_address['postcode']) . "',
                                '" . cleanInput($conn_destination, $row_address['city']) . "',
                                '" . cleanInput($conn_destination, $row_address['other']) . "',
                                '" . cleanInput($conn_destination, $row_address['phone']) . "',
                                '" . cleanInput($conn_destination, $row_address['phone_mobile']) . "',
                                '" . cleanInput($conn_destination, $row_address['vat_number']) . "',
                                '" . cleanInput($conn_destination, $row_address['dni']) . "',
                                '" . $row_address['date_add'] . "',
                                '" . $row_address['date_upd'] . "',
                                " . $row_address['deleted'] . "
                             )";

                            if ($conn_destination->query($sql_insert_address) === TRUE) {
                                echo 'New address: ' . $row_address['address1'] . ' <br>';

                            } else {
                                echo "Error: " . $sql . "<br>" . $conn_destination->error;
                                die();
                            }
                        }
                    }
                }
            } else {
                echo "Error: " . $sql . "<br>" . $conn_destination->error;
                die();
            }
        } else {
            $counter_exist++;
            echo 'Custumer (' . $row["email"] . ') exist in database (' . $id_customer . '), do nothing.<br>';
        }
    }
}

echo '<br><hr>Existing customers: <b>' . $counter_exist . '</b> New customers: <b>' . $counter_non_exist . '</b>';

function getIdCustomer($email, $conn, $prefix)
{
    $result = $conn->query("SELECT id_customer FROM " . $prefix . "customer WHERE email = '" . $email . "' LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row["id_customer"];
    } else {
        return false;
    }
}

function cleanInput($conn, $string = '')
{
    if (!empty($string)) {
        $stripsql = $conn->real_escape_string($string);
        return $stripsql;
    }
}

$conn_source->close();
$conn_destination->close();
?>