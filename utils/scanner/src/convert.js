import { Address } from '@ton/core';


const response = (data) => {
    console.log(JSON.stringify(data));
};

const addresses = {};
for (let address of process.argv[2].split(','))
    addresses[address] = Address.parse(address).toString();
response({'addresses': addresses});
