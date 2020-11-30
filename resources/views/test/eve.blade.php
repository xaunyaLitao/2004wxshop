<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>
<script>
    let arr=[12,16,17,-1,18,190]
    let status=arr.every(function (item,index,array) {
        if(item>0){
            return true;
        }else{
            return false;
        }
    })
    console.log(status);
</script>
</body>
</html>