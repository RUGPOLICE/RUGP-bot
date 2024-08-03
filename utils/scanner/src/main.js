import { Address } from '@ton/core';
import { checkForHoneypot } from './checker.js';


const response = (data) => {
    console.log(JSON.stringify(data));
};

const checkHoneypot = async (address, dex) => {
    if (Address.isFriendly(address) || Address.isRaw(address))
        response(await checkForHoneypot(Address.parse(address), dex));
    else
        response({ success: false, message: 'Invalid address' });
};

const address = process.argv[2];
const dex = process.argv[3].split(',');
checkHoneypot(address, dex).catch(e => response({ success: false, message: e.message, stack: e.stack }));
