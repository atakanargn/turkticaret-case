import pika

# RabbitMQ bağlantı bilgileri
connection_params = pika.ConnectionParameters(
    host='localhost',
    port=5672,
    virtual_host='/',
    credentials=pika.PlainCredentials('rabbitmq', 'rabbitmq')
)

# RabbitMQ bağlantısı oluştur
connection = pika.BlockingConnection(connection_params)
# Kanal oluşturma
channel = connection.channel()

# Kuyruk oluşturma (varsa kontrol edilir)
channel.queue_declare('mail_list', False, False, False, False)

# Mesaj alma


def on_message(ch, method, properties, body):
    print("Mesaj alındı:", body.decode())
    ch.basic_ack(delivery_tag=method.delivery_tag)


# Mesaj alma fonksiyonunu tetikleme
channel.basic_consume('mail_list', on_message)

# Mesajları dinleme
channel.start_consuming()
