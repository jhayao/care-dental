

create or replace table booking_items
(
    id         bigint unsigned auto_increment primary key,
    booking_id bigint unsigned                       not null,
    item_type  enum ('package', 'service')           not null,
    item_id    bigint unsigned                       not null,
    created_at timestamp default current_timestamp() null,
    updated_at timestamp default current_timestamp() null on update current_timestamp()
)
    collate = utf8mb4_unicode_ci;





create or replace table packages
(
    id               bigint unsigned auto_increment
        primary key,
    posted_by        int                         not null,
    package_name     varchar(255)                not null,
    description      varchar(255)                not null,
    inclusions       text                        null,
    status           enum ('Active', 'Inactive', 'Archived') not null,
    price            decimal(10, 2)              null,
    duration_minutes int default 60              not null,
    created_at       timestamp                   null,
    updated_at       timestamp                   null
)
    collate = utf8mb4_unicode_ci;



create or replace table services
(
    id               bigint unsigned auto_increment
        primary key,
    posted_by        int                         not null,
    service_name     varchar(255)                not null,
    description      longtext                    not null,
    service_image    varchar(255)                not null,
    status           enum ('Active', 'Inactive', 'Archived') not null,
    price            decimal(10, 2)              null,
    duration_minutes int                         not null,
    created_at       timestamp                   null,
    updated_at       timestamp                   null
)
    collate = utf8mb4_unicode_ci;

create or replace table package_items
(
    id          bigint unsigned auto_increment
        primary key,
    packages_id bigint unsigned not null,
    services_id bigint unsigned not null,
    created_at  timestamp       null,
    updated_at  timestamp       null,
    constraint package_items_packages_id_foreign
        foreign key (packages_id) references packages (id)
            on delete cascade,
    constraint package_items_services_id_foreign
        foreign key (services_id) references services (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;



create or replace table users
(
    id                bigint unsigned auto_increment
        primary key,
    first_name        varchar(255)                                             not null,
    last_name         varchar(255)                                             not null,
    address_          varchar(255)                                             null,
    email             varchar(255)                                             not null,
    email_verified_at timestamp                                                null,
    pword             text                                                     not null,
    user_type         enum ('admin', 'staff', 'patient')                       not null,
    status_           enum ('Active', 'Inactive', 'Archived') default 'Active' null,
    proof_file        varchar(255)                                             null,
    remember_token    varchar(100)                                             null,
    created_at        timestamp                                                null,
    updated_at        timestamp                                                null,
    gender            varchar(20)                                              null,
    dob               date                                                     null,
    reference_no      varchar(50)                                              null,
    category          enum ('None', 'Senior', 'PWD')          default 'None'   not null,
    discount          decimal(5, 2)                           default 0.00     not null,
    constraint users_email_unique
        unique (email)
)
    collate = utf8mb4_unicode_ci;

create or replace table bookings
(
    id               bigint unsigned auto_increment
        primary key,
    user_id          bigint unsigned                                                                                   not null,
    staff_id         int                                                                                               null,
    booking_date     date                                                                                              not null,
    time_slot        time                                                                                              not null,
    status           enum ('pending', 'confirmed', 'cancelled', 'rescheduled', 'refunded') default 'pending'           null,
    created_at       datetime                                                              default current_timestamp() not null,
    updated_at       datetime                                                              default current_timestamp() not null on update current_timestamp(),
    booking_fee      decimal(10, 2)                                                        default 50.00               not null,
    discount         decimal(10, 2)                                                        default 0.00                null,
    total_amount     decimal(10, 2)                                                        default 0.00                null,
    cancelled_at     datetime                                                                                          null,
    appointment_date date                                                                  default curdate()           not null,
    appointment_time time                                                                  default '09:00:00'          not null,
    duration_minutes int                                                                   default 60                  not null,
    reminder_sent    tinyint(1)                                                            default 0                   null,
    constraint bookings_user_id_foreign
        foreign key (user_id) references users (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create or replace table carts
(
    id         bigint unsigned auto_increment
        primary key,
    user_id    bigint unsigned not null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint carts_user_id_foreign
        foreign key (user_id) references users (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create or replace table cart_items
(
    id            bigint unsigned auto_increment
        primary key,
    cart_id       bigint unsigned not null,
    itemable_type varchar(255)    not null,
    itemable_id   bigint unsigned not null,
    created_at    timestamp       null,
    updated_at    timestamp       null,
    constraint cart_items_cart_id_foreign
        foreign key (cart_id) references carts (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create or replace index cart_items_itemable_type_itemable_id_index
    on cart_items (itemable_type, itemable_id);

create or replace table dentist_calendar
(
    id             int auto_increment
        primary key,
    user_id        bigint unsigned                       not null,
    available_date date                                  not null,
    start_time     time                                  not null,
    end_time       time                                  not null,
    created_at     timestamp default current_timestamp() not null,
    updated_at     timestamp                             null on update current_timestamp(),
    constraint fk_user
        foreign key (user_id) references users (id)
            on update cascade on delete cascade
)
    charset = utf8mb4;

create or replace table payments
(
    id                bigint unsigned auto_increment
        primary key,
    booking_id        bigint unsigned                                                                               not null,
    total_price       decimal(10, 2)                                                                                not null,
    payment_method    varchar(255)                                                                                  not null,
    status            enum ('pending', 'approved', 'declined', 'cancelled', 'refunded') default 'pending'           null,
    xendit_invoice_id varchar(255)                                                                                  not null,
    xendit_payment_id varchar(255)                                                                                  null,
    payment_date      timestamp                                                         default current_timestamp() not null on update current_timestamp(),
    created_at        timestamp                                                                                     null,
    updated_at        timestamp                                                                                     null,
    constraint payments_booking_id_foreign
        foreign key (booking_id) references bookings (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

-- Default Users (Password: password)
INSERT INTO users (first_name, last_name, address_, email, pword, user_type, status_, created_at, updated_at) VALUES 
('Admin', 'User', 'Admin Address', 'admin@example.com', '$2y$12$SDdxu63B99bLG/5cTXeHC.o4w50MhX6OVmDI2e5q/G1P3DGb0PydS', 'admin', 'Active', NOW(), NOW()),
('Staff', 'User', 'Staff Address', 'staff@example.com', '$2y$12$SDdxu63B99bLG/5cTXeHC.o4w50MhX6OVmDI2e5q/G1P3DGb0PydS', 'staff', 'Active', NOW(), NOW()),
('Patient', 'User', 'Patient Address', 'patient@example.com', '$2y$12$SDdxu63B99bLG/5cTXeHC.o4w50MhX6OVmDI2e5q/G1P3DGb0PydS', 'patient', 'Active', NOW(), NOW()),
('Saminodin', 'Admin', 'Admin Address', 'dsaminodin@gmail.com', '$2y$12$SDdxu63B99bLG/5cTXeHC.o4w50MhX6OVmDI2e5q/G1P3DGb0PydS', 'admin', 'Active', NOW(), NOW()),
('Fretchel Ann', 'Mahinay', 'Staff Address', 'fretchelannmahinay22@gmail.com', '$2y$12$SDdxu63B99bLG/5cTXeHC.o4w50MhX6OVmDI2e5q/G1P3DGb0PydS', 'staff', 'Active', NOW(), NOW()),
('TG', 'Bus', 'Address', 'tgbus@gmail.com', '$2y$12$SDdxu63B99bLG/5cTXeHC.o4w50MhX6OVmDI2e5q/G1P3DGb0PydS', 'staff', 'Active', NOW(), NOW());
