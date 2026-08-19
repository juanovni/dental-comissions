<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="pgsql">
            <directory>tests/pgsql</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing" force="true"/>
        <env name="DB_CONNECTION" value="pgsql" force="true"/>
        <env name="DB_DATABASE" value="dental_commissions_mvp_testing" force="true"/>
        <env name="DB_USERNAME" value="dental" force="true"/>
        <env name="DB_PASSWORD" value="dental" force="true"/>
        <env name="CACHE_STORE" value="array" force="true"/>
        <env name="SESSION_DRIVER" value="array" force="true"/>
        <env name="QUEUE_CONNECTION" value="sync" force="true"/>
    </php>
</phpunit>