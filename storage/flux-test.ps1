$ErrorActionPreference = 'Continue'
function Enviar($url, $metodo, $cookies, $post) {
    $req = [System.Net.HttpWebRequest]::Create("$url")
    $req.Method = $metodo
    $req.AllowAutoRedirect = $false
    $req.CookieContainer = $cookies
    $req.UserAgent = 'diagnostico'
    if ($post) {
        $req.ContentType = 'application/x-www-form-urlencoded'
        $bytes = [Text.Encoding]::UTF8.GetBytes($post)
        $req.ContentLength = $bytes.Length
        $stream = $req.GetRequestStream()
        $stream.Write($bytes, 0, $bytes.Length)
        $stream.Close()
    }
    try {
        $resp = $req.GetResponse()
        $reader = New-Object IO.StreamReader($resp.GetResponseStream())
        $body = $reader.ReadToEnd()
        return @{ status = [int]$resp.StatusCode; location = $resp.Headers['Location']; body = $body }
    } catch [System.Net.WebException] {
        $r = $_.Exception.Response
        if ($r) { return @{ status = [int]$r.StatusCode; location = $r.Headers['Location']; body = '' } }
        return @{ status = 'EXC'; location = $null; body = $_.Exception.Message }
    }
}

$base = 'http://127.0.0.1/PortalTramites/public'
$cookies = New-Object System.Net.CookieContainer
$cookies.Capacity = 100

# 1) home desde WAMP
$r1 = Enviar "$base/" 'GET' $cookies $null
Write-Output ("1. home => " + $r1.status)

# extraer _token de la home (formulario de consulta)
if ($r1.body -match 'name="_token" value="([^"]+)"') { $token = $Matches[1] } else { $token = $null }
Write-Output ("2. csrf: " + $(if ($token) { 'token=' + $token.Substring(0, [Math]::Min(12, $token.Length)) + '...' } else { 'NO ENCONTRADO' }))

if ($token) {
    # 2) solicitar token de consulta
    $datos = 'curp=GARC900101HMCXXX01&email_contacto=test%40ejemplo.com&_token=' + [Uri]::EscapeDataString($token)
    $r2 = Enviar "$base/consulta/token" 'POST' $cookies $datos
    Write-Output ("3. consulta/token => " + $r2.status + " -> " + $r2.location)

    # 3) verificar token valido
    if ($r2.body -match 'name="_token" value="([^"]+)"') { $token2 = $Matches[1]; $datos2 = 'token=000000&_token=' + [Uri]::EscapeDataString($token2) } else { $datos2 = $null }

    # 4) acceder a consulta/solicitudes sin sesion -> 302 a home
    $r3 = Enviar "$base/consulta/solicitudes" 'GET' $cookies $null
    Write-Output ("4. consulta/solicitudes => " + $r3.status + " -> " + $r3.location)

    # 5) enviar solicitud de tramite
    $r4 = Enviar "$base/tramites" 'GET' $cookies $null
    Write-Output ("5. tramites => " + $r4.status)
}

# 6) correcciones (token invalido -> 404 esperado)
$r5 = Enviar "$base/correcciones/token-invalido" 'GET' $cookies $null
Write-Output ("6. correcciones/token-invalido => " + $r5.status)
