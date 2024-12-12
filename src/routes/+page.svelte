<script lang="ts">
  import { onMount } from 'svelte';

  let balance: number = 0;
  let amount: string = '';
  let pin: string = '';
  let newPin: string = '';
  let isAuthenticated: boolean = false;
  let message: string = '';
  let transactions: Array<{ transac_type: string, amount: number, timestamp: string }> = [];
  let exchangeRates: Record<string, number> = {};
  let selectedCurrency: string = 'USD';
  let convertedBalance: number = 0;

  const currencies = ['USD', 'EUR', 'GBP', 'JPY'];

  const userPin = '1234';

  function mockEncryptData(data: any) {
    return JSON.stringify(data);
  }

  async function fetchExchangeRates() {
    try {
      const response = await fetch('https://api.exchangerate-api.com/v4/latest/PHP');
      const data = await response.json();
      exchangeRates = data.rates;
      convertBalance();
    } catch (error) {
      console.error('Error fetching exchange rates:', error);
    }
  }

  function convertBalance() {
    if (exchangeRates[selectedCurrency]) {
      convertedBalance = balance * exchangeRates[selectedCurrency];
    }
  }

  async function performTransaction(type: string) {
    try {
        const numAmount = parseFloat(amount);
        if (!amount || isNaN(numAmount) || numAmount <= 0) {
            message = 'Please enter a valid positive amount';
            return;
        }

        let newBalance = type === 'withdraw' ? balance - numAmount : balance + numAmount;

        if (type === 'withdraw' && newBalance < 0) {
            message = 'Insufficient funds';
            return;
        }

        const transactionData = {
            payload: [
                {
                    transac_type: type === 'withdraw' ? 'Withdrawal' : 'Deposit', // Ensure correct casing
                    amount: numAmount,
                    balance_after: newBalance,
                    timestamp: new Date().toISOString(),
                },
            ],
        };

        const encryptedResponse = mockEncryptData(transactionData);

        const response = await fetch(
            'http://localhost:8000/Api/routes.php?request=addtransaction',
            {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: encryptedResponse,
            }
        );

        const textResponse = await response.text();
        let result = JSON.parse(textResponse);

        if (result.success) {
            balance = newBalance; // Update balance after successful transaction
            message = `${type.charAt(0).toUpperCase() + type.slice(1)} successful`;
            amount = '';
            await loadTransactions(); // Reload transactions to ensure consistency
            convertBalance();
        } else {
            message = result.error || 'Transaction failed';
        }
    } catch (error) {
        console.error('Transaction error:', error);
        message = 'Error processing transaction';
    }
}

  async function loadTransactions() {
      try {
          const response = await fetch('http://localhost:8000/Api/routes.php?request=gettransactions', {
              method: 'GET',
              headers: { 'Content-Type': 'application/json' }
          });

          const textResponse = await response.text();
          console.log('Raw transactions response:', textResponse);

          let result;
          try {
              result = JSON.parse(textResponse);
          } catch (e) {
              console.error('Failed to parse transactions JSON:', e);
              message = 'Server response is not valid JSON';
              return;
          }

          if (Array.isArray(result)) {
              transactions = result.map(trans => ({
                  ...trans,
                  amount: parseFloat(trans.amount)
              }));

              // Calculate the balance based on transactions
              balance = transactions.reduce((acc, trans) => {
                  return trans.transac_type === 'Deposit' ? acc + trans.amount : acc - trans.amount;
              }, 0);
              console.log('Calculated balance after loading transactions:', balance);
              convertBalance();
          } else {
              throw new Error('Invalid transactions data');
          }
      } catch (error) {
          console.error('Error loading transactions:', error);
          message = 'Error loading transactions';
      }
  }

  function authenticate() {
    if (pin === userPin) {
      isAuthenticated = true;
      loadTransactions();
    } else {
      message = 'Invalid PIN';
    }
  }

  function logout() {
    isAuthenticated = false;
    pin = '';
    message = 'Logged out successfully';
  }

  function checkBalance() {
    message = `Your current balance is ₱${balance.toFixed(2)}`;
  }

  function showMiniStatement() {
    message = 'Mini-statement shown below.';
  }

  function changePin() {
    if (newPin.length === 4) {
      message = 'PIN changed successfully';
    } else {
      message = 'Please enter a 4-digit PIN';
    }
  }

  onMount(() => {
    fetchExchangeRates();
    if (isAuthenticated) {
      loadTransactions();
    }
  });
</script>

<main class="max-w-lg mx-auto p-5 bg-bdo-blue">
  <div class="flex justify-center mb-4">
    <img src="/download.png" alt="BDO Logo" style="height: 100px; width: auto; border-radius: 0.5rem;" />
</div>
  {#if !isAuthenticated}
    <div class="bg-white p-5 rounded-lg shadow-md">
      <input
        type="password"
        bind:value={pin}
        placeholder="Enter PIN"
        maxlength="4"
        class="w-full p-2 border border-gray-300 rounded mb-4"
      />
      <button class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600" on:click={authenticate}>Login</button>
    </div>
  {:else}
    <div class="bg-white p-5 rounded-lg shadow-md">
      <div class="flex justify-between items-center mb-4">
        <div class="text-xl font-semibold">Current Balance: ₱{balance.toFixed(2)}</div>
        <select bind:value={selectedCurrency} on:change={convertBalance} class="p-2 border border-gray-300 rounded">
          {#each currencies as currency}
            <option value={currency}>{currency}</option>
          {/each}
        </select>
      </div>
      <div class="text-xl font-semibold mb-4">Converted Balance: {convertedBalance.toFixed(2)} {selectedCurrency}</div>
      
      <div class="flex flex-col gap-4 mb-4">
        <input
          type="number"
          bind:value={amount}
          placeholder="Enter amount"
          min="0"
          class="w-full p-2 border border-gray-300 rounded"
        />
        <div class="flex gap-4">
          <button class="flex-1 bg-green-500 text-white py-2 rounded hover:bg-green-600" on:click={() => performTransaction('deposit')}>Deposit</button>
          <button 
            class="flex-1 bg-red-500 text-white py-2 rounded hover:bg-red-600 disabled:bg-gray-400"
            on:click={() => performTransaction('withdraw')}
            disabled={parseFloat(amount) > balance}
          >
            Withdraw
          </button>
        </div>
      </div>
  
      {#if message}
        <div class="p-3 mb-4 rounded bg-blue-100 text-blue-800">{message}</div>
      {/if}
  
      <div class="mt-4">
        <h3 class="text-lg font-semibold mb-2">Recent Transactions</h3>
        <div class="max-h-72 overflow-y-auto">
          {#each transactions as transaction}
            <div class="flex justify-between items-center p-2 border-b border-gray-200">
              <div>
                <span>{transaction.transac_type}</span>
                <span>₱{transaction.amount.toFixed(2)}</span>
                <span>{new Date(transaction.timestamp).toLocaleString()}</span>
              </div>
            </div>
          {/each}
        </div>
      </div>

      <button class="w-full mt-4 bg-gray-500 text-white py-2 rounded hover:bg-gray-600" on:click={logout}>Exit</button>
    </div>
  {/if}
</main>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');

  body {
    font-family: 'Roboto', sans-serif;
  }

  .bg-bdo-blue {
    background-color: #014ea8; /* Updated background color */
  }
</style>
