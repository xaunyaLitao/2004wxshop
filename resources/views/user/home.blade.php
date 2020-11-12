<!DOCTYPE html>
<html>

<head>
    @section('header')
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=9; IE=8; IE=7; IE=EDGE">
    <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
    <title>我的订单</title>
    <link rel="icon" href="/assets/img/favicon.ico">

    <link rel="stylesheet" type="text/css" href="/css/webbase.css" />
    <link rel="stylesheet" type="text/css" href="/css/pages-seckillOrder.css" />
    @show
</head>

<body>
<!-- 头部栏位 -->
<!--页面顶部-->
@section('body')

@show

@section('footerjs')
    <script src="/js/plugins/jquery/jquery.min.js"></script>
    <script>
        $(function(){
            $("#service").hover(function(){
                $(".service").show();
            },function(){
                $(".service").hide();
            });
            $("#shopcar").hover(function(){
                $("#shopcarlist").show();
            },function(){
                $("#shopcarlist").hide();
            });

        })
    </script>
    <script type="text/javascript" src="/js/plugins/jquery.easing/jquery.easing.min.js"></script>
    <script type="text/javascript" src="/js/plugins/sui/sui.min.js"></script>
    <script type="text/javascript" src="/js/plugins/jquery-placeholder/jquery.placeholder.min.js"></script>
    <script type="text/javascript" src="/js/widget/nav.js"></script>
@show
</body>
</html>
