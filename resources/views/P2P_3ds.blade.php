<html lang="en">
<head><title>3DS Page Redirect</title></head>
<body>
<form action="{{ $acsUrl }}" method="post" id="form_3ds" style="display: none">
    <input type="hidden" name="MD" value="{{ $orderId }}"/>
    <input type="hidden" name="PaReq" value="{{ $PaReq }}"/>
    <input type="hidden" name="TermUrl" value="{{ $siteUrl . 'p2p-status' }}"/>
    <button type="submit">Подтвердить</button>
</form>
<script type="text/javascript">
    document.getElementById("form_3ds").submit();
</script>
</body>
</html>
