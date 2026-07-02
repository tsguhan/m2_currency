define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    // Return an initialization function
    return function (config, element) {
        const $form = $(element);

        const form = document.getElementById('mappingForm');
        const outputDisplay = document.getElementById('outputDisplay');
        const swapBtn = document.getElementById('swapBtn');

        const primarySelect = document.getElementById('primarySelect');
        const secondarySelect = document.getElementById('secondarySelect');
        const radioGroup = document.getElementById('radioGroup');

        const primaryError = document.getElementById('primaryError');
        const secondaryError = document.getElementById('secondaryError');
        const radioError = document.getElementById('radioError');

        let isSwapped = false;

        // Clear error highlights on raw changes
        primarySelect.addEventListener('change', () => clearError(primarySelect, primaryError));
        secondarySelect.addEventListener('change', () => clearError(secondarySelect, secondaryError));
        radioGroup.addEventListener('change', () => {
            const selectedRadio = radioGroup.querySelector('input[type="radio"]:checked');
            if (selectedRadio) {
                radioError.style.display = 'none';
            }
        });

        function showError(inputEl, errorEl, message) {
            if (inputEl) inputEl.classList.add('input-error');
            errorEl.innerHTML = `⚠️ ${message}`;
            errorEl.style.display = 'block';
        }

        function clearError(inputEl, errorEl) {
            if (inputEl) inputEl.classList.remove('input-error');
            errorEl.style.display = 'none';
        }

        // SWAP LOGIC
        swapBtn.addEventListener('click', function() {
            const primaryLabel = document.getElementById('primaryLabel');
            const secondaryLabel = document.getElementById('secondaryLabel');

            const tempOptions = primarySelect.innerHTML;
            primarySelect.innerHTML = secondarySelect.innerHTML;
            secondarySelect.innerHTML = tempOptions;

            isSwapped = !isSwapped;

            if (isSwapped) {
                primaryLabel.textContent = "Select Currency (Dropdown):";
                secondaryLabel.textContent = "Select Currency (Multiselect - Hold Ctrl/Cmd):";
            } else {
                primaryLabel.textContent = "Select Currency (Dropdown):";
                secondaryLabel.textContent = "Select Currency (Multiselect - Hold Ctrl/Cmd):";
            }

            primarySelect.selectedIndex = 0;
            secondarySelect.selectedIndex = -1;
            clearError(primarySelect, primaryError);
            clearError(secondarySelect, secondaryError);
        });

        // AJAX FORM SUBMISSION + VALIDATION
        //form.addEventListener('submit', function(event) {
        $form.on('submit', function (e) {
            event.preventDefault();

            clearError(primarySelect, primaryError);
            clearError(secondarySelect, secondaryError);
            radioError.style.display = 'none';

            const formData = new FormData(form);
            const primaryVal = formData.get('primary');
            const secondaryVals = formData.getAll('secondary[]');

            // Getting single picked option from radio button
            const selectedNotification = radioGroup.querySelector('input[type="radio"]:checked')?.value;

            let isValid = true;

            // 1. Validation: Single Dropdown
            if (!primaryVal || primaryVal.trim() === "") {
                const missingContext = isSwapped ? "a currency" : "any currency";
                showError(primarySelect, primaryError, `Please pick ${missingContext} from the options list.`);
                isValid = false;
            }

            // 2. Validation: Multiselect Box
            if (secondaryVals.length === 0) {
                const missingContext = isSwapped ? "currency" : "any currency";
                showError(secondarySelect, secondaryError, `Please select at least one of the available ${missingContext}.`);
                isValid = false;
            }

            // 3. Validation: Radio Buttons
            if (!selectedNotification) {
                showError(null, radioError, "You must select a time period to show the chart.");
                isValid = false;
            }

            if (!isValid) return;

            // --- ALL VALIDATIONS PASSED: TRIGGER AJAX ---
            outputDisplay.innerHTML = '<p class="empty-message">Sending request...</p>';

            let payload = {};
            if (!isSwapped) {
                payload = {
                    primary: primaryVal,
                    secondary: secondaryVals,
                    period: selectedNotification, // single string instead of an array
                    swapped: false
                };
            } else {
                payload = {
                    secondary: primaryVal,
                    primary: secondaryVals,
                    period: selectedNotification, // single string instead of an array
                    swapped: true
                };
            }

            fetch(form.action, {
                method: form.method,
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(response => {
                    if (!response.ok) throw new Error('Network response error');
                    return response.json();
                })
                .then(data => {
                    outputDisplay.innerHTML = '';
                    const res = data.json || payload;

                    if (!res.swapped) {
                        res.tools.forEach(tool => {
                            const entryDiv = document.createElement('div');
                            entryDiv.className = 'mapping-entry';
                            entryDiv.innerHTML = `
                        <strong>Mapped Entry:</strong> <br>
                        Currency base: <b>${res.currency_exchanges.base}</b>
                        Currency rate: <b>${res.currency_exchanges.rate}</b><br>
                        <small>Period: <b>${res.currency_exchanges.date}</b></small>
                    `;
                            entryDiv.innerHTML += `
                                <div>
                                    ${res.history_data_for_chart.template}
                                </div>
                            `;
                            outputDisplay.appendChild(entryDiv);
                        });
                    } else {
                        res.departments.forEach(dept => {
                            const entryDiv = document.createElement('div');
                            entryDiv.className = 'mapping-entry';
                            entryDiv.innerHTML = `
                        <strong>Mapped Entry (Swapped):</strong> <br>
                        Currency base: <b>${res.currency_exchanges.base}</b>
                        Currency rate: <b>${res.currency_exchanges.rate}</b><br>
                        <small>Period: <b>${res.currency_exchanges.date}</b></small>
                    `;
                            entryDiv.innerHTML += `
                                <div>
                                    ${res.history_data_for_chart.template}
                                </div>
                            `;
                            outputDisplay.appendChild(entryDiv);
                        });
                    }
                })
                .catch(error => {
                    outputDisplay.innerHTML = `<div class="ajax-error">AJAX Error: ${error.message}</div>`;
                });
        });
    };
});
