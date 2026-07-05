const webpush = require('web-push');

const vapidKeys = webpush.generateVAPIDKeys();

console.log('=========================================');
console.log('Public Key:');
console.log(vapidKeys.publicKey);
console.log('');
console.log('Private Key:');
console.log(vapidKeys.privateKey);
console.log('=========================================');
