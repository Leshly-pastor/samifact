export const functions = {
    data() {
        return {
            loading_search_exchange_rate: false,
            loading_search: false,
            percentage_igv: 0.18,
        };
    },
    methods: {
        searchExchangeRate() {
            return new Promise((resolve) => {
                this.loading_search_exchange_rate = true;
                this.$http
                    .post(`/services/exchange_rate`, this.form)
                    .then((response) => {
                        let res = response.data;
                        if (res.success) {
                            this.data = res.data;
                            this.form.buy = res.data[this.form.cur_date].buy;
                            this.form.sell = res.data[this.form.cur_date].sell;
                            this.$message.success(res.message);
                        } else {
                            this.$message.error(res.message);
                            this.loading_search_exchange_rate = false;
                        }
                        resolve();
                    })
                    .catch((error) => {
                        console.log(error.response);
                        this.loading_search_exchange_rate = false;
                    })
                    .then(() => {
                        this.loading_search_exchange_rate = false;
                    });
            });
        },

        searchServiceNumber() {
            return new Promise((resolve) => {
                this.loading_search = true;
                let identity_document_type_name = "";
                if (this.form.identity_document_type_id === "6") {
                    identity_document_type_name = "ruc";
                }
                if (this.form.identity_document_type_id === "1") {
                    identity_document_type_name = "dni";
                }
                this.$http
                    .get(
                        `/services/${identity_document_type_name}/${this.form.number}`
                    )
                    .then((response) => {
                        console.log(response.data);
                        let res = response.data;
                        if (res.success) {
                            this.form.name = res.data.name;
                            this.form.trade_name = res.data.trade_name;
                            this.form.address = res.data.address;
                            this.form.department_id = res.data.department_id;
                            this.form.province_id = res.data.province_id;
                            this.form.district_id = res.data.district_id;
                            this.form.phone = res.data.phone;
                        } else {
                            this.$message.error(res.message);
                        }
                        resolve();
                    })
                    .catch((error) => {
                        console.log(error.response);
                    })
                    .then(() => {
                        this.loading_search = false;
                    });
            });
        },
        async getPercentageIgv() {
            await this.$http
                .post(`/store/get_igv`, {
                    establishment_id: this.form.establishment_id,
                    date: this.form.date_of_issue,
                })
                .then((response) => {
                    this.percentage_igv = response.data;
                });
        },
        async getPercentageIgvWithParams(establishment_id, date_of_issue) {
            await this.$http
                .post(`/store/get_igv`, {
                    establishment_id: establishment_id,
                    date: date_of_issue,
                })
                .then((response) => {
                    this.percentage_igv = response.data;
                });
        },
    },
};
export const advance = {
    methods: {
        saveAdvanceDocument() {
            this.$http
                .post(`/advances/advance_document`, this.form_cash_document)
                .then((response) => {
                    if (!response.data.success) {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => console.log(error));
        },
        enoughAdvance(base = "form") {
            let advance = this.payment_destinations.find(
                (payment) => payment.id == "advance"
            );
            let [payment] = this[base].payments;

            let final_balance = advance.final_balance;
            let payment_amount = payment.payment;

            return final_balance >= payment_amount;
        },
        payWithAdvanceDocument(personProp = "customer_id") {
            let result = undefined;
            if (this.document.payments.length == 1) {
                let [payment] = this.document.payments;
                if (payment.payment_destination_id == "advance") {
                    let advance = this.payment_destinations.find(
                        (payment) => payment.id == "advance"
                    );
                    let person_id = undefined;
                    if (this.document[personProp] != undefined) {
                        person_id = this.document[personProp];
                    }

                    this.document.payments[0].person_id = person_id;
                    result = advance.advance_id;
                }
            }
            this.form_cash_document.advance_id = null;
            return result;
        },
        payWithAdvance(personProp = "customer_id") {
            let result = undefined;
            if (this.form.payments.length == 1) {
                let [payment] = this.form.payments;
                if (payment.payment_destination_id == "advance") {
                    let advance = this.payment_destinations.find(
                        (payment) => payment.id == "advance"
                    );
                    let person_id = undefined;
                    if (this.form[personProp] != undefined) {
                        person_id = this.form[personProp];
                    } else if (
                        this.document &&
                        this.document[personProp] != undefined
                    ) {
                        person_id = this.document[personProp];
                    }

                    this.form.payments[0].person_id = person_id;
                    result = advance.advance_id;
                }
            }
            this.form_cash_document.advance_id = null;
            return result;
        },
        removeAdvanceFromDestinations() {
            this.payment_destinations = this.payment_destinations.filter(
                (payment) => payment.id !== "advance"
            );
            console.log(
                "🚀 ~ removeAdvanceFromDestinations ~ this.payment_destinations:",
                this.payment_destinations
            );
        },
        checkHasAdvance(idx) {
            if (this.form.payments.length > 1) {
                let payment = this.form.payments[idx];
                let payment_destination_id = payment.payment_destination_id;
                if (payment_destination_id === "advance") {
                    this.$message({
                        showClose: true,
                        type: "warning",
                        message:
                            "No se puede seleccionar 'adelanto' en una forma de pago diferente a las demás.",
                    });
                    //elige otro destino pero que no sea el que tenga el id "advance"
                    let other = this.payment_destinations.find(
                        (payment) => payment.id !== "advance"
                    );
                    this.form.payments[idx].payment_destination_id = other
                        ? other.id
                        : other;

                    return false;
                }
            }
            return true;
        },
        async getAdvance(personId) {
            this.removeAdvanceFromDestinations();
            const response = await this.$http(
                `/advances/get-advance/${personId}`
            );
            if (response.status === 200) {
                let { data } = response;
                let { success } = data;
                if (success) {
                    this.payment_destinations.unshift(data);
                } else {
                    this.payment_destinations =
                        this.payment_destinations.filter((payment) => {
                            return payment.id !== "advance";
                        });

                    this.form.payments.map((payment) => {
                        payment.payment_destination_id =
                            this.payment_destinations[0].id;
                    });
                }
            }
        },
    },
};
export const cash = {
    methods: {
        async getCash(user_id) {
            let { admin_seller_cash } = this.configuration;
            console.log(admin_seller_cash);
            if (!admin_seller_cash || this.typeUser != "admin") return;
            const response = await this.$http.get("/cash/get_cash/" + user_id);
            let error = false;
            if (response.status === 200) {
                const { data } = response;
                if (data && data.length > 0) {
                    this.payment_destinations = data;
                }
                this.$message({
                    showClose: true,
                    type: "success",
                    message: "Este comprobante será destinado al vendedor",
                });
                this.form.user_id = user_id;
            } else {
                error = true;
            }
            if (error) {
                this.$message({
                    showClose: true,
                    type: "warning",
                    message: "El vendedor no tiene cajas aperturadas",
                });
                this.form.payments.map((payment) => {
                    payment.payment_destination_id = null;
                });
            }
        },
    },
};
export const exchangeRate = {
    methods: {
        async searchExchangeRateByDate(exchange_rate_date) {
            let currency = this.currency_types.find(
                (currency) => currency.id === this.form.currency_type_id
            );
            if (currency.id !== "PEN" && currency.id !== "USD") {
                let response = await this.$http.get(
                    `/exchange_currency/${exchange_rate_date}/${currency.id}`
                );
                let success = response.data.success;
                if (!success) {
                    this.$message.error(response.data.message);
                }
                return parseFloat(response.data.sale);
            } else {
                try {
                    let response = await this.$http.get(
                        `/services/exchange/${exchange_rate_date}`
                    );
                    return parseFloat(response.data.sale);
                } catch (error) {
                    if (currency.id === "USD") {
                        
                        let response = await this.$http.get(
                            `/exchange_currency/${exchange_rate_date}/${currency.id}`
                        );
                        return parseFloat(response.data.sale);
                    }
                }
            }
        },
    },
};

export const serviceNumber = {
    data() {
        return {
            loading_search: false,
        };
    },
    methods: {
        filterProvince() {
            this.form.province_id = null;
            this.form.district_id = null;
            this.filterProvinces();
        },
        filterProvinces() {
            this.provinces = this.all_provinces.filter((f) => {
                return f.department_id === this.form.department_id;
            });
        },
        filterDistrict() {
            this.form.district_id = null;
            this.filterDistricts();
        },
        filterDistricts() {
            this.districts = this.all_districts.filter((f) => {
                return f.province_id === this.form.province_id;
            });
        },
        async searchServiceNumberByType() {
            if (this.form.number === "") {
                this.$message.error("Ingresar el número a buscar");
                return;
            }
            let identity_document_type_name = "";
            if (this.form.identity_document_type_id === "6") {
                identity_document_type_name = "ruc";
            }
            if (this.form.identity_document_type_id === "1") {
                identity_document_type_name = "dni";
            }
            this.loading_search = true;
            let response = await this.$http.get(
                `/services/${identity_document_type_name}/${this.form.number}`
            );
            if (response.data.success) {
                let data = response.data.data;
                this.form.name = data.name;
                this.form.trade_name = data.trade_name;
                this.form.address = data.address;
                this.form.location_id = data.location_id;
                // this.form.department_id = data.department_id
                // this.form.province_id = data.province_id
                // this.form.district_id = data.district_id
                this.form.phone = data.phone;
                // this.filterProvinces()
                // this.filterDistricts()
            } else {
                this.$message.error(response.data.message);
            }
            this.loading_search = false;
        },
        async searchServiceNumber() {
            if (this.form.number === "") {
                this.$message.error("Ingresar el número a buscar");
                return;
            }
            this.loading_search = true;
            let response = await this.$http.get(
                `/services/ruc/${this.form.number}`
            );
            if (response.data.success) {
                let data = response.data.data;
                this.form.name = data.name;
                this.form.trade_name = data.trade_name;
            } else {
                this.$message.error(response.data.message);
            }
            this.loading_search = false;
        },
    },
};

// Funciones para payments - fee
// Usado en:
// purchases
export const fnPaymentsFee = {
    data() {
        return {};
    },
    methods: {
        initDataPaymentCondition() {
            this.readonly_date_of_due = false;
            this.form.date_of_due = this.form.date_of_issue;
        },
        calculatePayments() {
            let payment_count = this.form.payments.length;
            let total = this.form.total;

            let payment = 0;
            let amount = _.round(total / payment_count, 2);

            _.forEach(this.form.payments, (row) => {
                payment += amount;
                if (total - payment < 0) {
                    amount = _.round(total - payment + amount, 2);
                }
                row.payment = amount;
            });
        },
        clickAddFee() {
            this.form.date_of_due = moment().format("YYYY-MM-DD");
            this.form.fee.push({
                id: null,
                date: moment().format("YYYY-MM-DD"),
                currency_type_id: this.form.currency_type_id,
                amount: 0,
            });
            this.calculateFee();
        },
        clickAddFeeNew() {
            let firstCreditPayment = null;

            if (this.creditPaymentMethod.length > 0) {
                firstCreditPayment = this.creditPaymentMethod[0];
            }

            let date = moment(this.form.date_of_issue)
                .add(firstCreditPayment.number_days, "days")
                .format("YYYY-MM-DD");

            this.form.date_of_due = date;

            this.form.fee.push({
                id: null,
                purchase_id: null,
                payment_method_type_id: firstCreditPayment.id,
                date: date,
                currency_type_id: this.form.currency_type_id,
                amount: 0,
            });

            this.calculateFee();
        },
        calculateFee() {
            let fee_count = this.form.fee.length;
            let total = this.form.total;

            let accumulated = 0;
            let amount = _.round(total / fee_count, 2);
            _.forEach(this.form.fee, (row) => {
                accumulated += amount;
                if (total - accumulated < 0) {
                    amount = _.round(total - accumulated + amount, 2);
                }
                row.amount = amount;
            });
        },
        clickRemoveFee(index) {
            this.form.fee.splice(index, 1);
            this.calculateFee();
        },
    },
};

// Funciones para asignar series por usuario para multiples tipos de documentos
// Usado en:
// purchases
export const setDefaultSeriesByMultipleDocumentTypes = {
    data() {
        return {};
    },
    methods: {
        generalDisabledSeries() {
            if (this.authUser === undefined) return false;

            return (
                this.configuration.restrict_series_selection_seller &&
                this.authUser.type !== "admin"
            );
        },
        generalSetDefaultSerieByDocumentType(document_type_id) {
            if (this.authUser !== undefined) {
                if (this.authUser.multiple_default_document_types) {
                    const default_document_type_serie = _.find(
                        this.authUser.default_document_types,
                        { document_type_id: document_type_id }
                    );

                    if (default_document_type_serie) {
                        const exist_serie = _.find(this.series, {
                            id: default_document_type_serie.series_id,
                        });
                        if (exist_serie)
                            this.form.series_id =
                                default_document_type_serie.series_id;
                    }
                }
            }
        },
    },
};

// funciones para sistema por puntos
// Usado en:
// invoice_generate.vue
// pos/payment.vue

export const pointSystemFunctions = {
    data() {
        return {
            customer_accumulated_points: 0,
            calculate_customer_accumulated_points: 0,
            total_exchange_points: 0,
            total_points_by_sale: 0,
        };
    },
    methods: {
        setTotalPointsBySale(configuration) {
            if (configuration && configuration.enabled_point_system) {
                const calculate_points =
                    (this.form.total / configuration.point_system_sale_amount) *
                    configuration.quantity_of_points;
                this.total_points_by_sale = configuration.round_points_of_sale
                    ? parseInt(calculate_points)
                    : _.round(calculate_points, 2);
                // this.total_points_by_sale = _.round((this.form.total / configuration.point_system_sale_amount) * configuration.quantity_of_points, 2)
            }
        },
        recalculateUsedPointsForExchange(row) {
            if (row.item.exchanged_for_points)
                row.item.used_points_for_exchange = this.getUsedPoints(row);
        },
        async setCustomerAccumulatedPoints(customer_id, enabled_point_system) {
            if (enabled_point_system) {
                await this.$http
                    .get(`/persons/accumulated-points/${customer_id}`)
                    .then((response) => {
                        this.customer_accumulated_points = response.data;
                        this.calculate_customer_accumulated_points =
                            response.data; //para calculos
                        this.calculateNewPoints();
                    });
            }
        },
        setTotalExchangePoints() {
            this.total_exchange_points = this.getTotalExchangePointsItems();
            this.calculateNewPoints();
        },
        hasPointsAvailable() {
            return this.calculate_customer_accumulated_points >= 0;
        },
        calculateNewPoints() {
            this.calculate_customer_accumulated_points =
                this.customer_accumulated_points - this.total_exchange_points;
        },
        validateExchangePoints() {
            if (!this.hasPointsAvailable()) {
                return {
                    success: false,
                    message: `El total de puntos a canjear excede los puntos acumulados: ${this.calculate_customer_accumulated_points} puntos`,
                };
            }

            return {
                success: true,
            };
        },
        getExchangePointDescription(row) {
            return `¿Desea canjearlo por ${this.getUsedPoints(row)} puntos?`;
        },
        getUsedPoints(row) {
            return _.round(row.item.quantity_of_points * row.quantity, 2);
        },
        getTotalExchangePointsItems() {
            return _.sumBy(this.form.items, (row) => {
                return row.item.exchanged_for_points
                    ? this.getUsedPoints(row)
                    : 0;
            });
        },
    },
};

// funciones para descuentos globales
// Usado en:
// tenant\purchases\form.vue
// resources\js\components\secondary\ListRestrictItems.vue

export const operationsForDiscounts = {
    data() {
        return {
            global_discount_types: [],
            global_discount_type: {},
            is_amount: true,
            total_global_discount: 0,
        };
    },
    computed: {
        isGlobalDiscountBase() {
            return this.config.global_discount_type_id === "02";
        },
    },
    methods: {
        deleteDiscountGlobal() {
            let discount = _.find(this.form.discounts, {
                discount_type_id: this.config.global_discount_type_id,
            });
            let index = this.form.discounts.indexOf(discount);

            if (index > -1) {
                this.form.discounts.splice(index, 1);
                this.form.total_discount = 0;
            }
        },
        discountGlobal(param_percentage_igv = null) {
            this.deleteDiscountGlobal();

            //input donde se ingresa monto o porcentaje
            let input_global_discount = parseFloat(this.total_global_discount);

            if (input_global_discount > 0) {
                const percentage_igv = param_percentage_igv
                    ? param_percentage_igv
                    : this.percentage_igv * 100;
                let base = this.isGlobalDiscountBase
                    ? parseFloat(this.form.total_taxed)
                    : parseFloat(this.form.total);
                let amount = 0;
                let factor = 0;

                if (this.is_amount) {
                    amount = input_global_discount;
                    factor = _.round(amount / base, 5);
                } else {
                    factor = _.round(input_global_discount / 100, 5);
                    amount = factor * base;
                }

                this.form.total_discount = _.round(amount, 2);

                // descuentos que afectan la bi
                if (this.isGlobalDiscountBase) {
                    this.form.total_taxed = _.round(
                        base - this.form.total_discount,
                        2
                    );
                    this.form.total_value = this.form.total_taxed;
                    this.form.total_igv = _.round(
                        this.form.total_taxed * (percentage_igv / 100),
                        2
                    );

                    //impuestos (isc + igv + icbper)
                    let total_plastic_bag_taxes = this.form
                        .total_plastic_bag_taxes
                        ? this.form.total_plastic_bag_taxes
                        : 0;

                    this.form.total_taxes = _.round(
                        this.form.total_igv +
                            this.form.total_isc +
                            total_plastic_bag_taxes,
                        2
                    );
                    this.form.total = _.round(
                        this.form.total_taxed + this.form.total_taxes,
                        2
                    );
                    this.form.subtotal = this.form.total;

                    if (this.form.total <= 0 && this.total_global_discount > 0)
                        this.$message.error(
                            "El total debe ser mayor a 0, verifique el tipo de descuento asignado (Configuración/Avanzado/Contable)"
                        );
                }
                // descuentos que no afectan la bi
                else {
                    this.form.total = _.round(this.form.total - amount, 2);
                }

                this.setGlobalDiscount(factor, _.round(amount, 2), base);
            }
        },
        changeTypeDiscount() {
            this.calculateTotal();
        },
        changeTotalGlobalDiscount() {
            this.calculateTotal();
        },
        setConfigGlobalDiscountType() {
            this.global_discount_type = _.find(this.global_discount_types, {
                id: this.config.global_discount_type_id,
            });
        },
        setGlobalDiscount(factor, amount, base) {
            this.form.discounts.push({
                discount_type_id: this.global_discount_type.id,
                description: this.global_discount_type.description,
                factor: factor,
                amount: amount,
                base: base,
                is_amount: this.is_amount,
            });
        },
    },
};

// funciones para restriccion de productos

// Usado en:
// resources\js\components\secondary\ListRestrictItems.vue
// modules\Order\Resources\assets\js\views\order_notes\partials\options.vue
// resources\js\views\tenant\documents\invoice_generate.vue
// resources\js\views\tenant\sale_notes\partials\option_documents.vue

export const fnRestrictSaleItemsCpe = {
    data() {
        return {};
    },
    computed: {
        fnApplyRestrictSaleItemsCpe() {
            if (this.configuration)
                return this.configuration.restrict_sale_items_cpe;

            return false;
        },
    },
    methods: {
        fnValidateRestrictSaleItemsCpe(form) {
            if (this.fnApplyRestrictSaleItemsCpe) {
                let errors_restricted = 0;

                form.items.forEach((row) => {
                    if (
                        this.fnIsRestrictedForSale(
                            row.item,
                            form.document_type_id
                        )
                    )
                        errors_restricted++;
                });

                if (errors_restricted > 0)
                    return this.fnGetObjectResponse(
                        false,
                        "No puede generar el comprobante, tiene productos restringidos."
                    );
            }

            return this.fnGetObjectResponse();
        },
        fnCheckIsInvoice(document_type_id) {
            return ["01", "03"].includes(document_type_id);
        },
        fnIsRestrictedForSale(item, document_type_id) {
            return (
                this.fnApplyRestrictSaleItemsCpe &&
                this.fnCheckIsInvoice(document_type_id) &&
                item != undefined &&
                item.restrict_sale_cpe
            );
        },
        fnGetObjectResponse(success = true, message = null) {
            return {
                success: success,
                message: message,
            };
        },
    },
};
