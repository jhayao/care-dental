create or replace table booking_fees
(
    id          bigint unsigned auto_increment
        primary key,
    booking_fee decimal(10, 2)              not null,
    status      enum ('Active', 'Inactive') not null,
    created_at  timestamp                   null,
    updated_at  timestamp                   null
)
    collate = utf8mb4_unicode_ci;

create or replace table booking_items
(
    id         bigint unsigned                       not null,
    booking_id bigint unsigned                       not null,
    item_type  enum ('package', 'service')           not null,
    item_id    bigint unsigned                       not null,
    created_at timestamp default current_timestamp() null,
    updated_at timestamp default current_timestamp() null on update current_timestamp()
)
    collate = utf8mb4_unicode_ci;

create or replace table cache
(
    `key`      varchar(255) not null
        primary key,
    value      mediumtext   not null,
    expiration int          not null
)
    collate = utf8mb4_unicode_ci;

create or replace table cache_locks
(
    `key`      varchar(255) not null
        primary key,
    owner      varchar(255) not null,
    expiration int          not null
)
    collate = utf8mb4_unicode_ci;

create or replace table failed_jobs
(
    id         bigint unsigned auto_increment
        primary key,
    uuid       varchar(255)                          not null,
    connection text                                  not null,
    queue      text                                  not null,
    payload    longtext                              not null,
    exception  longtext                              not null,
    failed_at  timestamp default current_timestamp() not null,
    constraint failed_jobs_uuid_unique
        unique (uuid)
)
    collate = utf8mb4_unicode_ci;

create or replace table job_batches
(
    id             varchar(255) not null
        primary key,
    name           varchar(255) not null,
    total_jobs     int          not null,
    pending_jobs   int          not null,
    failed_jobs    int          not null,
    failed_job_ids longtext     not null,
    options        mediumtext   null,
    cancelled_at   int          null,
    created_at     int          not null,
    finished_at    int          null
)
    collate = utf8mb4_unicode_ci;

create or replace table jobs
(
    id           bigint unsigned auto_increment
        primary key,
    queue        varchar(255)     not null,
    payload      longtext         not null,
    attempts     tinyint unsigned not null,
    reserved_at  int unsigned     null,
    available_at int unsigned     not null,
    created_at   int unsigned     not null
)
    collate = utf8mb4_unicode_ci;

create or replace index jobs_queue_index
    on jobs (queue);

create or replace table medical_records
(
    id         bigint unsigned auto_increment
        primary key,
    created_at timestamp null,
    updated_at timestamp null
)
    collate = utf8mb4_unicode_ci;

create or replace table migrations
(
    id        int unsigned auto_increment
        primary key,
    migration varchar(255) not null,
    batch     int          not null
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
    status           enum ('Active', 'Inactive') not null,
    price            decimal(10, 2)              null,
    duration_minutes int default 60              not null,
    created_at       timestamp                   null,
    updated_at       timestamp                   null
)
    collate = utf8mb4_unicode_ci;

create or replace table password_reset_tokens
(
    email      varchar(255) not null
        primary key,
    token      varchar(255) not null,
    created_at timestamp    null
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
    status           enum ('Active', 'Inactive') not null,
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

create or replace table sessions
(
    id            varchar(255)    not null
        primary key,
    user_id       bigint unsigned null,
    ip_address    varchar(45)     null,
    user_agent    text            null,
    payload       longtext        not null,
    last_activity int             not null
)
    collate = utf8mb4_unicode_ci;

create or replace index sessions_last_activity_index
    on sessions (last_activity);

create or replace index sessions_user_id_index
    on sessions (user_id);

create or replace table staff
(
    id         bigint unsigned auto_increment
        primary key,
    first_name varchar(255)                       not null,
    last_name  varchar(255)                       not null,
    address_   varchar(255)                       not null,
    email      varchar(255)                       not null,
    pword      text                               not null,
    created_at timestamp                          null,
    updated_at timestamp                          null,
    status_    tinyint(1)                         not null,
    role_      enum ('admin', 'staff', 'patient') not null
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

