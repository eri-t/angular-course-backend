CREATE DATABASE IF NOT EXISTS angular_course;
USE angular_course;

CREATE TABLE products(
    id int(255) auto_increment not null,
    name varchar(255),
    description text,
    price varchar(255),
    image varchar(255)
)ENGINE=InnoDb;