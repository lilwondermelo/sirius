<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ru">
	<head>
		<title>Конструктор данных</title>
		<link rel="stylesheet" href="css/style.css">
		<script src="js/jquery-3.5.1.min.js"></script>
		<script src="https://telegram.org/js/telegram-web-app.js"></script>
		<script src="js/main.js"></script>
		<script src="js/event-listener.js"></script>
		<script src="js/render.js"></script>
		<script src="js/api.js"></script>
		<script src="js/cart.js"></script>
	</head>
	<body>
		<div class="app_bgr"></div>
		<div id="app_container">
			<div class="flex_row main_title_row">
				<div class="main_title_logo"><img src="media/images/system/logo.svg" alt=""></div>
				<div class="main_title_header">Сириус</div>
				<div class="main_title_cart"><img src="media/icons/cart.svg" alt=""></div>
			</div>
			<div class="flex_row main_menu_row" id="main_menu">
				<div class="menu_item" cat-id=0>
					<div class="menu_item_img"><img src="media/images/menu/0.png" alt=""></div>
					<div class="menu_item_name">Главная</div>
				</div>
				<div class="menu_item" cat-id=4>
					<div class="menu_item_img"><img src="media/images/menu/1.png" alt=""></div>
					<div class="menu_item_name">Смесители</div>
				</div>
				<div class="menu_item" cat-id=18>
					<div class="menu_item_img"><img src="media/images/menu/2.png" alt=""></div>
					<div class="menu_item_name">ПП фитинги</div>
				</div>
			</div>
			<div class="flex_row main_table_row" id="main_table">
				
			</div>
		</div>
		<div class="popup" id="popup">
			<div class="popup_inner"></div>
		</div>
		<div class="tip">
			Подсказка
		</div>
		<script src="js/ready.js"></script>
	</body>
</html>