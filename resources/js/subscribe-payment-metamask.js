const ERC20_TRANSFER_SELECTOR = '0xa9059cbb';

const root = document.getElementById('subscribe-metamask-payment');
if (!root) {
    // MetaMask UI not on this page.
} else {
    const recipient = root.dataset.recipient;
    const usdtContract = root.dataset.usdtContract;
    const usdcContract = root.dataset.usdcContract;
    const chainId = Number(root.dataset.chainId);
    const stablecoinAmount = root.dataset.stablecoinAmount;
    const ethAmountWei = root.dataset.ethAmountWei;
    const recordUrl = root.dataset.recordUrl;
    const messageEl = document.getElementById('metamask-payment-message');
    const actionButtons = root.querySelectorAll('.subscribe-metamask-actions button');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const showMessage = (text, isError = false) => {
        if (!messageEl) {
            return;
        }
        messageEl.textContent = text;
        messageEl.hidden = text === '';
        messageEl.classList.toggle('subscribe-payment-message--error', isError);
    };

    const setBusy = (busy) => {
        actionButtons.forEach((button) => button.toggleAttribute('disabled', busy));
    };

    const assertAddress = (address, label) => {
        if (typeof address !== 'string' || !/^0x[a-fA-F0-9]{40}$/.test(address)) {
            throw new Error(`${label} is not configured.`);
        }
    };

    const encodeErc20Transfer = (toAddress, amount) => {
        assertAddress(toAddress, 'Payment recipient wallet');

        const to = toAddress.slice(2).toLowerCase().padStart(64, '0');
        const amountHex = BigInt(amount).toString(16).padStart(64, '0');

        return `${ERC20_TRANSFER_SELECTOR}${to}${amountHex}`;
    };

    const getProvider = () => {
        if (typeof window.ethereum === 'undefined') {
            throw new Error('MetaMask is not installed.');
        }

        return window.ethereum;
    };

    const requestAccounts = async (provider) => {
        const accounts = await provider.request({ method: 'eth_requestAccounts' });

        if (!Array.isArray(accounts) || accounts.length === 0) {
            throw new Error('No MetaMask account selected.');
        }

        return accounts[0];
    };

    const ensureChain = async (provider) => {
        const chainIdHex = `0x${chainId.toString(16)}`;

        try {
            await provider.request({
                method: 'wallet_switchEthereumChain',
                params: [{ chainId: chainIdHex }],
            });
        } catch (error) {
            if (error?.code === 4902) {
                throw new Error('Please add this network to MetaMask and try again.');
            }

            throw error;
        }
    };

    const sendEth = async (provider, from) => {
        assertAddress(recipient, 'Payment recipient wallet');

        return provider.request({
            method: 'eth_sendTransaction',
            params: [
                {
                    from,
                    to: recipient,
                    value: `0x${BigInt(ethAmountWei).toString(16)}`,
                },
            ],
        });
    };

    const sendStablecoin = async (provider, from, contractAddress, tokenLabel) => {
        assertAddress(contractAddress, `${tokenLabel} contract`);

        const data = encodeErc20Transfer(recipient, stablecoinAmount);
        if (!data.startsWith(ERC20_TRANSFER_SELECTOR) || data.length !== 138) {
            throw new Error('Failed to prepare token transfer.');
        }

        return provider.request({
            method: 'eth_sendTransaction',
            params: [
                {
                    from,
                    to: contractAddress,
                    value: '0x0',
                    data,
                },
            ],
        });
    };

    const recordTransaction = async (txHash, token) => {
        const response = await fetch(recordUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ tx_hash: txHash, token }),
        });

        const body = await response.json();

        if (!response.ok) {
            throw new Error(body.message ?? 'Unable to record transaction.');
        }

        return body;
    };

    const pay = async (token) => {
        if (!recipient || !recordUrl || !chainId || !stablecoinAmount) {
            showMessage('MetaMask payment is not configured.', true);
            return;
        }

        setBusy(true);
        showMessage('');

        try {
            const provider = getProvider();
            const from = await requestAccounts(provider);
            await ensureChain(provider);

            let txHash;
            if (token === 'eth') {
                txHash = await sendEth(provider, from);
            } else if (token === 'usdt') {
                if (!usdtContract) {
                    throw new Error('USDT is not configured.');
                }
                txHash = await sendStablecoin(provider, from, usdtContract, 'USDT');
            } else if (token === 'usdc') {
                if (!usdcContract) {
                    throw new Error('USDC is not configured.');
                }
                txHash = await sendStablecoin(provider, from, usdcContract, 'USDC');
            } else {
                throw new Error('Unsupported payment token.');
            }

            if (typeof txHash !== 'string' || !/^0x[a-fA-F0-9]{64}$/.test(txHash)) {
                throw new Error('Unexpected transaction response from MetaMask.');
            }

            const result = await recordTransaction(txHash, token);
            showMessage(result.message ?? 'Transaction recorded.');
        } catch (error) {
            const message =
                error?.message === 'User rejected the request.'
                    ? 'Transaction cancelled.'
                    : (error?.message ?? 'MetaMask payment failed.');
            showMessage(message, true);
        } finally {
            setBusy(false);
        }
    };

    document.getElementById('metamask-pay-usdt')?.addEventListener('click', () => pay('usdt'));
    document.getElementById('metamask-pay-usdc')?.addEventListener('click', () => pay('usdc'));
    document.getElementById('metamask-pay-eth')?.addEventListener('click', () => pay('eth'));
}
