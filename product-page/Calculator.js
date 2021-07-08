class Calculator {

    constructor() {

    }

    /*
    |  @param  value: Number 
    |  @description: Вычисляет процентный коэффициент числа. 
    |  @return Float
    */
    toСoefficientPercent(value) {
        try {

            return Number.parseFloat((Number.parseInt(value) / 100)).toFixed(2);

        } catch (error) {

        }
    }

    /*
    |  @param  value: Number || Float , percent: Number 
    |  @description: Вычисляет процентную надбавку числа. 
    |  @return Float
    */
    getExtraChargeInPercent(value, percent) {
        try {

            return Number.parseFloat(Number.parseFloat(value) * (Number.parseFloat(1) + Number.parseFloat(this.toСoefficientPercent(percent)))).toFixed(2);

        } catch (error) {

        }
    }

    /*
    |  @param  value: Number || Float , exCharge: Number || Float
    |  @description: Вычисляет надбавку числа к числу (сумма). 
    |  @return Float
    */
    getExtraChargeInValue(value, exCharge) {
        try {

            return Number.parseFloat(Number.parseFloat(value) + Number.parseFloat(exCharge)).toFixed(2);

        } catch (error) {

        }
    }

    /*
    |  @param  quantity: Number , cost: Float, exCharge: Number || Float
    |  @description: Вычисляет стоимость quantity-едениц с процентной надбавкой от значения cost. 
    |  @return Float
    */
    getSumExChargePercent(quantity, cost, percent) {
        try {

            return Number.parseFloat((Number.parseFloat(quantity) * Number.parseFloat(this.getExtraChargeInPercent(cost, percent)))).toFixed(2);

        } catch (error) {

        }
    }

    /*
    |  @param  quantity: Number , cost: Float, exCharge: Number || Float
    |  @description: Вычисляет стоимость quantity-едениц с числовой надбавкой от значения cost. 
    |  @return Float
    */
    getSumExChargeValue(quantity, cost, exCharge) {
        try {

            return Number.parseFloat((Number.parseFloat(quantity) * Number.parseFloat(this.getExtraChargeInValue(cost, exCharge)))).toFixed(2);

        } catch (error) {

        }
    }

}

const productCalculator = new Calculator();