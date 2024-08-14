import { Address } from '@ton/core';


const response = (data) => {
    console.log(JSON.stringify(data));
};

const address = process.argv[2];
response({'address': Address.parse(address).toString()})

