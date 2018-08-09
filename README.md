# redis-queue
A simple php queue based on redis

## 1.Used with namespace

> The directory structure as below

```shell
├── app
│   ├── ClientTest.php
│   ├── Jobs
│   │   └── TestDemo.php
│   └── ServerTest.php
├── composer.json
└── public
    └── index.php
```

Follow link:

https://github.com/zhaokongdong/demo/tree/master/demo1

## 2. Used without namespace

> The directory structure as below

```shell
├── Jobs                                
│   └── TestDemo.php                    
├── composer.json                       
├── dispatch.php  
└── worker.php     
```

Follow link:

https://github.com/zhaokongdong/demo/tree/master/demo2
