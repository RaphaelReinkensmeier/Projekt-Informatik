<?php
/**
 * In dieser Datei werden alle Funktionen definiert, die auf anderen Seiten aufgerufen werden können.
 * Die Idee ist, dass die Übersichtlichkeit und Wartbarkeit des Codes erhöht wird, wenn Funktionen, die
 * mehrfach verwendet werden, an einer zentralen Stelle definiert werden.
 * 
 * Du solltest alle eigenen Funktionen, die du in deinem Projekt verwendest, ebenfalls hier definieren.
 */


require_once 'config.php';

/* Die connect-Funktion stellt eine Verbindung zur Datenbank her und gibt das Verbindungobjekt zurück. Die Daten zur Verbindung mit der Datenbank
* werden in der Datei config.php definiert.
*/
function connect() {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
    }
    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

/**
 * @verifikation_link: Der Link, der in der E-Mail enthalten sein soll. Er muss auf deine index.php zeigen und den Parameter "code" enthalten.
 * @subject: Der Betreff der E-Mail
 * @message: Der Inhalt der E-Mail
 * @headers: Hier muss die Absenderadresse eingetragen werden. Das muss eine existierende E-Mail-Adresse sein. Für unser Projekt kannst du als 
 *          Absenderadresse no-reply@fes-informatik.de verwenden. 
 */
function send_verification_email($email, $verification_code) {
    $verification_link = "https://fes-informatik.de/dob/g02/projekt/index.php?page=verify&code=" . $verification_code;
    $subject = "Bitte bestätige deine Registrierung";
    $message = "Hallo,\n\nBitte bestätige deine Registrierung, indem du auf folgenden Link klickst:\n\n$verification_link\n\nDanke!";
    $headers = "Content-Type: text/plain; charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@fes-informatik.de\r\n";
    
    // E-Mail senden und Ergebnis zurückgeben
    return mail($email, $subject, $message, $headers);
}

// Diese Funktion überprüft, ob der Benutzer mit dem übergebenen Verifikationscode existiert und ob er schon verifiziert wurde.
function verify_user($verification_code) {
    $conn = connect();

    // Überprüfen, ob der Code existiert und noch nicht benutzt wurde
    $query = "SELECT id FROM users WHERE verification_code = ? AND is_verified = 0";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $verification_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_bind_result($stmt, $user_id);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        // Benutzer als verifiziert markieren
        $query = "UPDATE users SET is_verified = 1 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        mysqli_close($conn);
        return true;
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return false;
}

// Diese Funktion führt die eigentliche Registrierung durch. Sie überprüft, ob der Benutzername oder die E-Mail-Adresse schon existieren und fügt den Benutzer ein.
function register_user($username, $email, $password) {
    $conn = connect();

    // Prüfen, ob Benutzername oder E-Mail existieren
    $query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) { // Wenn die Datenbank mindestens eine Zeile zurückgibt, existiert der Benutzername oder die E-Mail-Adresse schon
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return false;
    }
    mysqli_stmt_close($stmt);

    // Passwort hashen
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $verification_code = bin2hex(random_bytes(32));

    // Benutzer einfügen
    $query = "INSERT INTO users (username, email, password, verification_code) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $verification_code);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $success = send_verification_email($email, $verification_code); // E-Mail senden und true in $success speichern, wenn alles geklappt hat.
    
    mysqli_close($conn);
    return $success;    // True zurückgeben, wenn die Registrierung erfolgreich war, sonst false.
}

// Diese Funktion loggt den Benutzer ein, wenn der Benutzername und das Passwort korrekt sind.
function login_user($username, $password) {
    $conn = connect();

    $query = "SELECT id, password, is_verified FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_id, $hashed_password, $is_verified);

    if (mysqli_stmt_fetch($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);

        if (password_verify($password, $hashed_password)) { // Passwort überprüfen.
            if ($is_verified) { // Prüfen, ob der Benutzer seine E-Mail-Adresse schon bestätigt hat.
                session_start();
                $_SESSION['user_id'] = $user_id;    // Nur dann wird die Benutzer-ID aus der Datenbank geladen.
                return true;
            }
        }
    }
    // Datenbankverbindung schließen und false zurückgeben, wenn der Benutzer nicht eingeloggt werden konnte.
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return false;
}

// Diese Funktion gibt den Benutzernamen zu einer Benutzer-ID zurück.
function get_username($user_id) {
    $conn = connect();
    
    $query = "SELECT username FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    
    return $user['username'] ?? null; // Falls kein Benutzer gefunden wurde, null zurückgeben
}

?>


