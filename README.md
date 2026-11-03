# controle-esp32
Sistema para controle da acesso utilizando ESP32 e Sensor RFID RC522

1) Clone/copie esse repositório para C:\xampp\htdocs\controle-acesso
2) XAMPP: inicie Apache e MySQL
3) phpMyAdmin (http://localhost/phpmyadmin) > Importar > database/schema.sql
4) Abra http://localhost/controle-acesso/ e crie o primeiro administrador
5) No ESP32:
   API_URL_ACESSO   = "http://IP-DO-PC/controle-acesso/api/acesso"
   API_URL_CADASTRO = "http://IP-DO-PC/controle-acesso/api/chaves/pendente"
Usuário/senha do MySQL: estão no config/config.php e devem ser alterados.

# Código para HELTEC V3
Esse código foi escrito voltado exclusivamente para o HELTEC ESP32 V3 e tem em consideração todo aparato já embutido no microcontrolador. Para que funcione em demais placas, será necessário a revisão e reajuste do código, principalmente em relação a tela OLED.
