create table profitbase_data
(
    id       bigint unsigned not null,
    service  varchar(20)     not null,
    house    varchar(50)     not null,
    category varchar(250)    not null,
    xml_data longblob        not null,
    constraint id
        unique (id, service, house, category)
)
    charset = utf8;

create table profitbase_tasks
(
    id      int auto_increment
        primary key,
    service varchar(50) not null,
    house   varchar(50) not null,
    command json        not null,
    constraint service
        unique (service, house)
)
    charset = utf8;