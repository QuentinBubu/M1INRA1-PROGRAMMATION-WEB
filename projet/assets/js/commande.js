'use strict';

const numeroCarte = document.querySelector('#numero_carte');
const expiration = document.querySelector('#expiration');

if (numeroCarte) {
    numeroCarte.addEventListener('input', () => {
        const chiffres = numeroCarte.value.replace(/\D/g, '').slice(0, 19);
        numeroCarte.value = chiffres.replace(/(.{4})/g, '$1 ').trim();
    });
}

if (expiration) {
    expiration.addEventListener('input', () => {
        const chiffres = expiration.value.replace(/\D/g, '').slice(0, 4);
        expiration.value = chiffres.length > 2
            ? chiffres.slice(0, 2) + '/' + chiffres.slice(2)
            : chiffres;
    });
}
