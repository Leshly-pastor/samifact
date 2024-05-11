<template>
    <el-dialog
        :title="titleDialog"
        :visible="showDialog"
        @close="close"
        @open="create"
    >
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.id }"
                        >
                            <label class="control-label">Moneda</label>
                            <el-select
                                v-model="form.id"
                                filterable
                                placeholder="Seleccione una moneda"
                                @change="changeCurrency"
                            >
                                <el-option
                                    v-for="currency in currencies"
                                    :key="currency.code"
                                    :label="currency.description"
                                    :value="currency.code"
                                ></el-option>
                            </el-select>

                            <!-- <el-input
                                v-model="form.id"
                                :readonly="recordId !== null"
                            ></el-input> -->
                            <small
                                class="text-danger"
                                v-if="errors.id"
                                v-text="errors.id[0]"
                            ></small>
                        </div>
                    </div>
                    <!-- <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.description }"
                        >
                            <label class="control-label">Descripción</label>
                            <el-input v-model="form.description"></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.description"
                                v-text="errors.description[0]"
                            ></small>
                        </div>
                    </div> -->
                    <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.symbol }"
                        >
                            <label class="control-label">Símbolo</label>
                            <el-input v-model="form.symbol"></el-input>
                            <small
                                class="text-danger"
                                v-if="errors.symbol"
                                v-text="errors.symbol[0]"
                            ></small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.active }"
                        >
                            <label class="control-label">Activo</label>
                            <el-switch
                                v-model="form.active"
                                active-text="Si"
                                inactive-text="No"
                            ></el-switch>
                            <small
                                class="text-danger"
                                v-if="errors.active"
                                v-text="errors.active[0]"
                            ></small>
                        </div>
                    </div>
                    <!-- <div class="col-md-4">
                        <div
                            class="form-group"
                            :class="{ 'has-danger': errors.manual_exchange }"
                        >
                            <label class="control-label"
                                >Tipo de cambio manual</label
                            >
                            <el-switch
                                v-model="form.manual_exchange"
                                active-text="Si"
                                inactive-text="No"
                            ></el-switch>
                            <small
                                class="text-danger"
                                v-if="errors.manual_exchange"
                                v-text="errors.manual_exchange[0]"
                            ></small>
                        </div>
                    </div> -->
                </div>
            </div>
            <div class="form-actions text-end mt-4">
                <el-button @click.prevent="close()">Cancelar</el-button>
                <el-button
                    type="primary"
                    native-type="submit"
                    :loading="loading_submit"
                    >Guardar</el-button
                >
            </div>
        </form>
    </el-dialog>
</template>

<script>
export default {
    props: ["showDialog", "recordId"],
    data() {
        return {
            loading_submit: false,
            titleDialog: null,
            resource: "currency_types",
            errors: {},
            form: {},
            currencies: [
                { code: "AFN", description: "Afghani" },
                { code: "EUR", description: "Euro" },
                { code: "ALL", description: "Lek" },
                { code: "DZD", description: "Dinar argelino" },
                { code: "USD", description: "Dólar estadounidense" },
                { code: "AOA", description: "Kwanza" },
                { code: "XCD", description: "Dólar del Caribe Oriental" },
                { code: "ARS", description: "Peso argentino" },
                { code: "AMD", description: "Dram armenio" },
                { code: "AWG", description: "Florín arubeño" },
                { code: "AUD", description: "Dólar australiano" },
                { code: "AZN", description: "Manat azerbaiyano" },
                { code: "BSD", description: "Dólar bahameño" },
                { code: "BHD", description: "Dinar bahreiní" },
                { code: "BDT", description: "Taka de Bangladesh" },
                { code: "BBD", description: "Dólar de Barbados" },
                { code: "BYR", description: "Rublo bielorruso" },
                { code: "BZD", description: "Dólar de Belice" },
                { code: "XOF", description: "Franco CFA de África Occidental" },
                { code: "BMD", description: "Dólar de Bermudas" },
                { code: "BTN", description: "Ngultrum" },
                { code: "INR", description: "Rupia india" },
                { code: "BOB", description: "Boliviano" },
                { code: "BOV", description: "Mvdol boliviano" },
                {
                    code: "BAM",
                    description: "Marco convertible de Bosnia-Herzegovina",
                },
                { code: "BWP", description: "Pula" },
                { code: "NOK", description: "Corona noruega" },
                { code: "BRL", description: "Real brasileño" },
                { code: "BND", description: "Dólar de Brunéi" },
                { code: "BGN", description: "Lev búlgaro" },
                { code: "KHR", description: "Riel camboyano" },
                { code: "CAD", description: "Dólar canadiense" },
                { code: "CVE", description: "Escudo de Cabo Verde" },
                { code: "KYD", description: "Dólar de Islas Caimán" },
                { code: "CLF", description: "Unidad de fomento chilena" },
                { code: "CLP", description: "Peso chileno" },
                { code: "CNY", description: "Yuan chino" },
                { code: "COP", description: "Peso colombiano" },
                { code: "COU", description: "Unidad de valor real colombiana" },
                { code: "KMF", description: "Franco comorense" },
                { code: "CDF", description: "Franco congoleño" },
                { code: "NZD", description: "Dólar neozelandés" },
                { code: "CRC", description: "Colón costarricense" },
                { code: "HRK", description: "Kuna croata" },
                { code: "CUC", description: "Peso cubano convertible" },
                { code: "CUP", description: "Peso cubano" },
                { code: "ANG", description: "Florín antillano neerlandés" },
                { code: "CZK", description: "Corona checa" },
                { code: "DKK", description: "Corona danesa" },
                { code: "DJF", description: "Franco yibutiano" },
                { code: "DOP", description: "Peso dominicano" },
                { code: "EGP", description: "Libra egipcia" },
                { code: "SVC", description: "Colón salvadoreño" },
                { code: "ERN", description: "Nakfa eritreo" },
                { code: "ETB", description: "Birr etíope" },
                { code: "FKP", description: "Libra de Islas Malvinas" },
                { code: "FJD", description: "Dólar fiyiano" },
                { code: "XPF", description: "Franco CFP" },
                { code: "GMD", description: "Dalasi gambiano" },
                { code: "GEL", description: "Lari georgiano" },
                { code: "GHS", description: "Cedi ghanés" },
                { code: "GIP", description: "Libra gibraltareña" },
                { code: "GTQ", description: "Quetzal guatemalteco" },
                { code: "GBP", description: "Libra esterlina británica" },
                { code: "GYD", description: "Dólar guyanés" },
                { code: "HTG", description: "Gourde haitiano" },
                { code: "HNL", description: "Lempira hondureño" },
                { code: "HKD", description: "Dólar de Hong Kong" },
                { code: "HUF", description: "Florín húngaro" },
                { code: "ISK", description: "Corona islandesa" },
                { code: "IRR", description: "Rial iraní" },
                { code: "IQD", description: "Dinar iraquí" },
                { code: "ILS", description: "Nuevo shekel israelí" },
                { code: "JMD", description: "Dólar jamaicano" },
                { code: "JPY", description: "Yen japonés" },
                { code: "JOD", description: "Dinar jordano" },
                { code: "KZT", description: "Tenge kazajo" },
                { code: "KES", description: "Chelín keniano" },
                { code: "KPW", description: "Won norcoreano" },
                { code: "KRW", description: "Won surcoreano" },
                { code: "KWD", description: "Dinar kuwaití" },
                { code: "KGS", description: "Som kirguís" },
                { code: "LAK", description: "Kip laosiano" },
                { code: "LBP", description: "Libra libanesa" },
                { code: "LSL", description: "Loti lesothense" },
                { code: "LRD", description: "Dólar liberiano" },
                { code: "LYD", description: "Dinar libio" },
                { code: "CHF", description: "Franco suizo" },
                { code: "MGA", description: "Ariary malgache" },
                { code: "MWK", description: "Kwacha malauí" },
                { code: "MYR", description: "Ringgit malayo" },
                { code: "MVR", description: "Rupia de Maldivas" },
                { code: "XOF", description: "Franco CFA de África Occidental" },
                { code: "MRO", description: "Uguiya mauritana" },
                { code: "MUR", description: "Rupia mauriciana" },
                { code: "MXN", description: "Peso mexicano" },
                {
                    code: "MXV",
                    description: "Unidad de inversión (UDI) mexicana",
                },
                { code: "MDL", description: "Leu moldavo" },
                { code: "MNT", description: "Tugrik mongol" },
                { code: "MZN", description: "Metical mozambiqueño" },
                { code: "MMK", description: "Kyat birmano" },
                { code: "NAD", description: "Dólar namibio" },
                { code: "NPR", description: "Rupia nepalí" },
                { code: "NZD", description: "Dólar neozelandés" },
                { code: "NIO", description: "Córdoba nicaragüense" },
                { code: "XOF", description: "Franco CFA de África Occidental" },
                { code: "NGN", description: "Naira nigeriana" },
                { code: "NIO", description: "Córdoba nicaragüense" },
                { code: "NOK", description: "Corona noruega" },
                { code: "OMR", description: "Rial omaní" },
                { code: "PKR", description: "Rupia pakistaní" },
                { code: "PAB", description: "Balboa panameño" },
                { code: "PGK", description: "Kina de Papúa Nueva Guinea" },
                { code: "PEN", description: "Sol peruano" },
                { code: "PHP", description: "Peso filipino" },
                { code: "PLN", description: "Zloty polaco" },
                { code: "QAR", description: "Rial catarí" },
                { code: "RON", description: "Leu rumano" },
                { code: "RUB", description: "Rublo ruso" },
                { code: "RWF", description: "Franco ruandés" },
                { code: "SHP", description: "Libra de Santa Elena" },
                { code: "SAR", description: "Riyal saudí" },
                { code: "RSD", description: "Dinar serbio" },
                { code: "SCR", description: "Rupia seychelense" },
                { code: "SLL", description: "Leona sierraleonesa" },
                { code: "SGD", description: "Dólar singapurense" },
                { code: "MKD", description: "Denar macedonio" },
                { code: "SBD", description: "Dólar de Islas Salomón" },
                { code: "SOS", description: "Chelín somalí" },
                { code: "ZAR", description: "Rand sudafricano" },
                { code: "LKR", description: "Rupia de Sri Lanka" },
                { code: "SDG", description: "Libra sudanesa" },
                { code: "SRD", description: "Dólar surinamés" },
                { code: "TOP", description: "Pa'anga tongano" },
                { code: "TTD", description: "Dólar de Trinidad y Tobago" },
                { code: "TND", description: "Dinar tunecino" },
                { code: "TRY", description: "Lira turca" },
                { code: "TMT", description: "Manat turcomano" },
                { code: "UGX", description: "Chelín ugandés" },
                { code: "UAH", description: "Grivna ucraniana" },
                {
                    code: "AED",
                    description: "Dírham de Emiratos Árabes Unidos",
                },
                {
                    code: "UYI",
                    description: "Peso en unidades indexadas uruguayo",
                },
                { code: "UYU", description: "Peso uruguayo" },
                { code: "UZS", description: "Som uzbeko" },
                { code: "VUV", description: "Vatu vanuatuense" },
                { code: "VEF", description: "Bolívar venezolano" },
                { code: "VND", description: "Dong vietnamita" },
                { code: "USD", description: "Dólar estadounidense" },
                {
                    code: "USD",
                    description: "Dólar estadounidense (día siguiente)",
                },
                { code: "XPF", description: "Franco CFP" },
                { code: "MAD", description: "Dirham marroquí" },
                { code: "YER", description: "Rial yemení" },
                { code: "ZMW", description: "Kwacha zambiano" },
                { code: "ZWL", description: "Dólar zimbabuense" },
                {
                    code: "XBA",
                    description:
                        "Unidad compuesta europea de mercados de bonos",
                },
                {
                    code: "XBB",
                    description: "Unidad monetaria europea de la Unión Europea",
                },
                { code: "XBC", description: "Unidad de cuenta europea (XBC)" },
                { code: "XBD", description: "Unidad de cuenta europea (XBD)" },
                { code: "XTS", description: "Código de prueba" },
                {
                    code: "XXX",
                    description: "Código de moneda sin especificar",
                },
                { code: "XAU", description: "Oro" },
                { code: "XPD", description: "Paladio" },
                { code: "XPT", description: "Platino" },
                { code: "XAG", description: "Plata" },
            ],
        };
    },
    created() {
        this.initForm();
    },
    methods: {
        changeCurrency(value) {
            const currency = this.currencies.find((c) => c.code === value);
            this.form.description = currency.description;
        },
        initForm() {
            this.errors = {};
            this.form = {
                id: null,
                description: null,
                symbol: null,
                active: true,
            };
        },
        create() {
            this.titleDialog = this.recordId ? "Editar Moneda" : "Nueva Moneda";
            if (this.recordId) {
                this.$http
                    .get(`/${this.resource}/record/${this.recordId}`)
                    .then((response) => {
                        this.form = response.data.data;
                    });
            }
        },
        submit() {
            this.loading_submit = true;
            this.$http
                .post(`/${this.resource}`, this.form)
                .then((response) => {
                    if (response.data.success) {
                        this.$message.success(response.data.message);
                        this.$eventHub.$emit("reloadData");
                        this.close();
                    } else {
                        this.$message.error(response.data.message);
                    }
                })
                .catch((error) => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data;
                    } else {
                        console.log(error);
                    }
                })
                .then(() => {
                    this.loading_submit = false;
                });
        },
        close() {
            this.$emit("update:showDialog", false);
            this.initForm();
        },
    },
};
</script>
