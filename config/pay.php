<?php
/**
 * Created by PhpStorm.
 * User: zjl
 * Date: 19-7-23
 * Time: 下午5:27
 */

return [
    'alipay' => [
        'app_id'         => '2016101000656668',
        'ali_public_key' => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjkBjrwlWGKw0394a6nHzS++fyV92D7J9TbhQ8J0J+t4WYDis1Cr793qrZRKLqspI90Nf57GC0RuizW0MkHdGKVCJ8KZDY24QlDflQ1rH0BGzRWFGS9cIwoWpuDvu9mihnmtXgq63ZoaUqNhkrkvCFH+s0g6f5kHp4lPT5nwSjwBz0xSX1TemcprPYnQSAg6AXK8fDOcegcG59LleAjW/9xpzs6M/CqGSFj11axzfS0JWwmTNCUE0XzoChh4q/wYBheR6rHA2OyJno9GmhXfsDZRDCPLa2FwT2AHlgYaMckm+VO+rUcmvFGzdL5f+RwsZgYiYMLuRaGBMiISiB/eaBwIDAQAB',
        'private_key'    => 'MIIEpQIBAAKCAQEA0Tiqn4L+el0UwtHx6eegTysZAyHsaPp5aI7Fyh7hgxXXeb9/prGeJqkHk3UVPmC9EniFRs/PQcookbq80/DAvB3U653I7x4vTT502QqcnoJsu4gZhXvr+GJyjus3ylzi1Otbe22U2UohMEi5gYDp9eR8omp1SPGKFJPIqpsqFbpU29hF5+UfVqNwv+XD9wDi/3MWnxK5NP5hrd4ksfegUjHFqnMUmX23L16O20M7m0gRZ0CNjNSbNs8ZtTuz8yZXwu86T7THiHuhKDwhAhpm3r6CsjPV+YT8ACzaQkODgYJ05nvHn4Hac0eyNiv+CayFu8Om+NBMsTb312Kq1dNXPQIDAQABAoIBAQCvzmwhiZOJAvpVEtGy+T3OCKsF5NWhCQhIAeDWDMj2u73Kwk3jptD8L4D8OaBWQDfgc4GalTjVUahxfHb7qQxrhq3KJ1YXCEg7IkZRswcUcwgnNSAs+Iq6tw8IZUMGOrMos3S17MjtJctppVKsLcVrKEpKuCZB2yVqURyHr8bXZaylfkvaljv+w5t9LnAnKqx3CNCNdv9km9jicpR1hLO/LJs58yFbQF2AlIFw5iOJNh637LL91zLFs+cpeRyPNg6xhuRZGLnNHhz5+oi7Na5ycxaCVN3o274mciuw+LNr61FuXIGz/pvxIoT1G/xzS6OTzd2pAdJVo7bsQT/oavUJAoGBAO0ikGVmfAewoXZTsccxsVvAFj3EJqjIz9XiJ5AHwxolvRiWiRPIkAQkj33awZTjI2ZotKa7vX3/6Ul02Yqukvi2GJ9iCs0q65MdjwOmo0/YKRVMWMs9xqkFm9v/G5MDSC5DlK4jTB3NUE2Prpyyk19C0z9eYfGZhiy9YqLrRWkPAoGBAOHdno7vwl8Jl6513P78Pos/JvZMXC0BElcuYzLd1jT0EPYMpFeRK1FvahkcqRaT4FCYzM0w5cxx6d9TQ9CW0PwSzx8ZJsx+WGHnDSlOMjLoV4WQa9+dLPm0o+TWm9PylrP223WGlY4Dsv4ouw0N+/PtAjYg4/NbMqCAbV2s+YLzAoGAal800qeUP4bWBtQoTShXXMbfszlH9jKOOG1IPe2dcR84ocz1FHDFWXZk/5mfeIAjIw8Y6ioRKHIhXZgS9Yi12OcmSGicW8hDAC0kOAkJ/QkD9M/YjOLbOHqRna/j2KCCQm6CRVMEE+JDgWdQdm4MeZDqXeSintO1QmB53IcZDbUCgYEAsjWHTmY0KYJLEJbkaLejrYCFgF3deJDY5LqwAElyItPTsh9lZ8YdtKJAx1GKxEqm+VCmftuu5PGYQuHSpvjoKYu5qmLQ7xZdK9n+03FkQDB8JeE+i+/atabmjb8ask88wv/qRj+LLsMSbmC6vWEkQ2Dklsq6sJ9rQaIzKG87aysCgYEAvDonnQfC6vSxNoO2wImd6hA0RAs6RNGn+brS6J76H01MzbBQVqf/JnLP5OgPL6XOrhejUnLaLkvodJ9lSYoShO94dhJSXop3m/rCvvEIt7xm/wfdh0H+b7lf2H3r0v7xnuEMfvL+STjzEMB79aVhDiKKXLCQohZ8SwB1JrPSmxg=',
        'log'            => [
            'file' => storage_path('logs/alipay.log'),
        ],
    ],

    'wechat' => [
        'app_id'      => '',
        'mch_id'      => '',
        'key'         => '',
        'cert_client' => '',
        'cert_key'    => '',
        'log'         => [
            'file' => storage_path('logs/wechat_pay.log'),
        ],
    ],
];